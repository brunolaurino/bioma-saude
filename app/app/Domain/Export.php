<?php

namespace App\Domain;

use App\Models\Alert;
use App\Models\Appointment;
use App\Models\ClinicalNote;
use App\Models\Consultation;
use App\Models\DataExport;
use App\Models\Patient;
use App\Models\StoredFile;
use App\Models\TherapySession;
use App\Security\Audit;
use App\Security\BlindIndex;
use App\Security\Consents;
use App\Security\Crypto;
use App\Support\Clock;
use App\Support\Fmt;
use App\Support\Settings;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Portabilidade (LGPD art. 18, V): um ZIP com JSON (máquina), CSV (planilha), HTML (leitura) e
 * os arquivos originais. O ZIP fica cifrado com a chave do paciente e o link vale 24 horas.
 */
class Export
{
    /** @return string token em claro do link de download */
    public static function build(Patient $patient): string
    {
        $data = new PatientData($patient);
        $sections = [
            'cadastro' => [[
                'nome' => $patient->name, 'email' => $patient->user?->email, 'telefone' => $patient->phone,
                'nascimento' => $patient->birth_date, 'altura_m' => $patient->height, 'objetivo' => $patient->objective,
                'inicio_acompanhamento' => $patient->started_on?->toDateString(),
            ]],
            'metas' => $patient->goals->map(fn ($g) => ['tipo' => $g->type, 'meta' => $g->target])->all(),
            'bioimpedancia' => $data->bio()->map(fn ($b) => ['data' => $b['d']->toDateString(), 'peso_kg' => $b['w'], 'gordura_pct' => $b['f'], 'musculo_kg' => $b['m'], 'visceral' => $b['v'], 'tmb_kcal' => $b['t']])->all(),
            'metas_diarias' => \App\Models\GoalEntry::of($patient)->orderBy('day')->get()->map(fn ($e) => ['data' => $e->day->toDateString(), 'tipo' => $e->type, 'valor' => is_bool($e->value) ? ($e->value ? 'sim' : 'não') : $e->value])->all(),
            'sintomas' => $data->symptoms(100000)->map(fn ($s) => ['data' => $s->day->toDateString(), 'sintoma' => $s->symptom, 'intensidade' => $s->intensity, 'observacao' => $s->note])->all(),
            'treinos' => $data->workouts()->map(fn ($w) => ['data' => $w->day->toDateString(), 'categoria' => $w->category, 'modalidade' => $w->modality, 'minutos' => $w->minutes, 'intensidade' => $w->intensity, 'observacao' => $w->note])->all(),
            'desabafos' => $data->journal(false)->map(fn ($j) => ['data' => $j->day->toDateString(), 'humor' => \App\Domain\Catalog::mood((int) $j->mood)['l'], 'temas' => implode(', ', (array) $j->tags), 'texto' => $j->text, 'compartilhado' => $j->shared ? 'sim' : 'não'])->all(),
            'terapia' => TherapySession::of($patient)->orderBy('starts_at')->get()->map(fn ($t) => ['data' => Clock::local($t->starts_at)->format('Y-m-d H:i'), 'status' => $t->status])->all(),
            'consultas' => Consultation::of($patient)->orderBy('day')->get()->map(fn ($c) => ['data' => $c->day->toDateString(), 'tipo' => $c->type, 'anotacao' => $c->note])->all(),
            'agenda' => Appointment::of($patient)->orderBy('starts_at')->get()->map(fn ($a) => ['data' => Clock::local($a->starts_at)->format('Y-m-d H:i'), 'tipo' => $a->type, 'status' => $a->status])->all(),
            'anotacoes_clinicas' => ClinicalNote::of($patient)->orderBy('created_at')->get()->map(fn ($n) => ['data' => Clock::local($n->created_at)->format('Y-m-d H:i'), 'texto' => $n->text])->all(),
            'alertas' => Alert::of($patient)->orderBy('day')->get()->map(fn ($a) => ['data' => $a->day->toDateString(), 'resumo' => $a->summary, 'revisado' => $a->resolved_at ? 'sim' : 'não'])->all(),
            'consentimentos' => Consents::history($patient)->map(fn ($c) => ['data' => (string) $c->created_at, 'finalidade' => Consents::PURPOSES[$c->purpose] ?? $c->purpose, 'acao' => $c->action === 'accept' ? 'aceite' : 'revogação', 'versao_aviso' => $c->notice_version])->all(),
        ];

        $tmpDir = sys_get_temp_dir().'/bioma-export-'.Str::random(12);
        mkdir($tmpDir, 0700);
        $zipPath = $tmpDir.'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('dados.json', json_encode(['gerado_em' => now()->toIso8601String(), 'consultorio' => Settings::get('clinic_name')] + $sections, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        foreach ($sections as $name => $rows) {
            $zip->addFromString("planilhas/$name.csv", self::csv($rows));
        }
        $zip->addFromString('leia-me.html', view('exports.readable', ['patient' => $patient, 'sections' => $sections, 'clinic' => Settings::get('clinic_name')])->render());

        $tmpFiles = [];
        foreach (StoredFile::of($patient)->get() as $f) {
            $tmp = $tmpDir.'/'.$f->id;
            Files::decryptTo($f, $tmp);
            $tmpFiles[] = $tmp;
            $zip->addFile($tmp, 'arquivos/'.$f->kind.'-'.$f->taken_on->format('Y-m-d').'-'.$f->name);
        }
        $zip->close();
        array_map('unlink', $tmpFiles);
        @rmdir($tmpDir);

        $export = new DataExport(['patient_id' => $patient->id, 'expires_at' => now()->addHours((int) config('bioma.export_hours'))]);
        $export->id = $export->newUniqueId();
        $token = Str::random(48);
        $export->token_hash = BlindIndex::token($token);
        $export->storage_name = Str::random(40);
        Crypto::encryptFile($patient->id, $export->id, $zipPath, self::dir().'/'.$export->storage_name);
        @unlink($zipPath);
        $export->save();
        Audit::log('privacy.export', 'export:'.$export->id, $patient->id);

        return $token;
    }

    public static function find(string $token, Patient $patient): ?DataExport
    {
        return DataExport::of($patient)->where('token_hash', BlindIndex::token($token))->where('expires_at', '>', now())->first();
    }

    public static function stream(DataExport $export)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'bio');
        Crypto::decryptFile($export->patient_id, $export->id, self::dir().'/'.$export->storage_name, $tmp);
        $export->forceFill(['downloaded_at' => now()])->save();
        Audit::log('privacy.export.download', 'export:'.$export->id, $export->patient_id);

        return response()->download($tmp, 'meus-dados-bioma-'.Clock::todayIso().'.zip', ['Cache-Control' => 'no-store, private'])->deleteFileAfterSend();
    }

    /** Apaga exportações vencidas (rodado por bioma:retencao). */
    public static function purgeExpired(): int
    {
        $n = 0;
        foreach (DataExport::where('expires_at', '<', now())->get() as $e) {
            @unlink(self::dir().'/'.$e->storage_name);
            $e->delete();
            $n++;
        }

        return $n;
    }

    private static function csv(array $rows): string
    {
        if (! $rows) {
            return "\xEF\xBB\xBF";
        }
        $h = fopen('php://temp', 'r+');
        fwrite($h, "\xEF\xBB\xBF");
        fputcsv($h, array_keys($rows[0]), ';');
        foreach ($rows as $r) {
            fputcsv($h, array_map(fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v, $r), ';');
        }
        rewind($h);

        return stream_get_contents($h);
    }

    private static function dir(): string
    {
        $dir = storage_path('app/private/exports');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        return $dir;
    }
}
