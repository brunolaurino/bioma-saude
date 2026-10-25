<?php

namespace App\Http\Controllers\Patient;

use App\Domain\Export;
use App\Domain\Files;
use App\Domain\Notice;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\DataSubjectRequest;
use App\Models\JournalEntry;
use App\Models\Professional;
use App\Models\StoredFile;
use App\Security\Audit;
use App\Security\Consents;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Canais da LGPD, sempre ligados: consentimentos, acesso, acessos da equipe, portabilidade, pedidos e exclusão. */
class PrivacyController extends Controller
{
    public const REQUEST_TYPES = [
        'acesso' => 'Confirmar e acessar meus dados',
        'correcao' => 'Corrigir dados incompletos ou errados',
        'eliminacao' => 'Eliminar dados que não precisam ser guardados',
        'informacao' => 'Saber com quem meus dados foram compartilhados',
        'oposicao' => 'Me opor a um tratamento',
        'portabilidade' => 'Levar meus dados para outro profissional',
    ];

    public function index()
    {
        $patient = $this->patient();

        return view('patient.privacy', [
            'shareDefault' => Consents::has($patient, 'journal_share'),
            'photos' => Consents::has($patient, 'photos'),
            'notice' => Notice::current(),
            'requests' => DataSubjectRequest::of($patient)->orderByDesc('created_at')->get(),
            'types' => self::REQUEST_TYPES,
            'lead' => Settings::leadName(),
        ]);
    }

    public function consent(Request $request)
    {
        $request->validate(['purpose' => ['required', Rule::in(['photos', 'journal_share'])], 'value' => ['required', 'boolean']]);
        $patient = $this->patient();
        $purpose = $request->input('purpose');

        if ($request->boolean('value')) {
            Consents::accept($patient, $purpose);

            return back()->with('status', 'Autorização registrada.');
        }

        Consents::revoke($patient, $purpose);
        if ($purpose === 'photos') {
            // Retirar o consentimento das fotos apaga as fotos (não fazem parte da guarda obrigatória).
            $files = StoredFile::of($patient)->where('kind', 'foto')->get();
            $files->each(fn ($f) => Files::purge($f));

            return back()->with('status', 'Autorização retirada. '.$files->count().' '.($files->count() === 1 ? 'foto apagada' : 'fotos apagadas').'.');
        }

        return back()->with('status', 'Desabafos novos ficam privados por padrão.');
    }

    public function myData()
    {
        $patient = $this->patient();
        $counts = [
            ['weight-scale', 'Bioimpedâncias', \App\Models\BioRecord::of($patient)->count()],
            ['list-check', 'Marcações de metas', \App\Models\GoalEntry::of($patient)->count()],
            ['notes-medical', 'Sintomas', \App\Models\SymptomEntry::of($patient)->count()],
            ['dumbbell', 'Treinos', \App\Models\Workout::of($patient)->count()],
            ['feather', 'Desabafos', JournalEntry::of($patient)->count()],
            ['images', 'Fotos', StoredFile::of($patient)->where('kind', 'foto')->count()],
            ['file-medical', 'Exames e laudos', StoredFile::of($patient)->whereIn('kind', ['exame', 'laudo'])->count()],
            ['file-lines', 'Planos alimentares', StoredFile::of($patient)->where('kind', 'plano')->count()],
            ['stethoscope', 'Consultas registradas', \App\Models\Consultation::of($patient)->count()],
        ];

        return view('patient.my-data', ['patient' => $patient, 'email' => $this->user()->email, 'counts' => $counts, 'history' => Consents::history($patient)]);
    }

    /** Quem da equipe acessou ou alterou os dados do paciente (vem da trilha de auditoria). */
    public function accesses()
    {
        $patient = $this->patient();
        $rows = DB::table('audit_log')->where('patient_id', $patient->id)->where(fn ($q) => $q->whereNull('actor_id')->orWhere('actor_id', '!=', $this->user()->id))
            ->whereNotIn('actor', ['sistema', 'anônimo'])->orderByDesc('id')->limit(100)->get();

        return view('patient.accesses', ['rows' => $rows]);
    }

    public function export()
    {
        $token = Export::build($this->patient());

        return redirect()->route('privacy')->with('export_token', $token)->with('status', 'Seu arquivo está pronto. O link vale por 24 horas.');
    }

    public function download(string $token)
    {
        $export = Export::find($token, $this->patient());
        abort_unless($export, 404, 'Este link expirou. Gere o arquivo de novo.');

        return Export::stream($export);
    }

    public function requestForm()
    {
        return view('patient.privacy-request', ['types' => self::REQUEST_TYPES, 'days' => config('bioma.dsr_days')]);
    }

    public function storeRequest(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::REQUEST_TYPES))],
            'details' => ['nullable', 'string', 'max:2000'],
        ], ['type.*' => 'Escolha o tipo de pedido.']);

        $patient = $this->patient();
        $dsr = new DataSubjectRequest(['patient_id' => $patient->id, 'type' => $data['type'], 'due_at' => now()->addDays((int) config('bioma.dsr_days'))]);
        $dsr->details = trim((string) ($data['details'] ?? '')) ?: null;
        $dsr->save();
        Audit::log('privacy.request', 'dsr:'.$dsr->id, $patient->id, ['type' => $data['type']]);

        foreach (Professional::where('role', 'master')->pluck('user_id') as $uid) {
            AppNotification::send($uid, 'shield-halved', 'Novo pedido de titular de dados para responder.', route('pro.requests'));
        }

        return redirect()->route('privacy')->with('status', 'Pedido enviado. A resposta chega em até '.config('bioma.dsr_days').' dias.');
    }

    public function deleteForm()
    {
        return view('patient.privacy-delete', ['lead' => Settings::leadName()]);
    }

    /**
     * Encerrar a conta: o acesso é bloqueado na hora; fotos e desabafos privados são apagados.
     * O prontuário fica guardado pelo prazo legal (Lei 13.787/2018) e um pedido de eliminação
     * é aberto para o responsável tratar o restante.
     */
    public function delete(Request $request)
    {
        $request->validate(['confirm' => ['required', 'in:EXCLUIR']], ['confirm.*' => 'Digite EXCLUIR para confirmar.']);
        $patient = $this->patient();
        $user = $this->user();

        DB::transaction(function () use ($patient, $user) {
            StoredFile::of($patient)->where('kind', 'foto')->get()->each(fn ($f) => Files::purge($f));
            JournalEntry::of($patient)->whereNull('shared_at')->get()->each->forceDelete();
            foreach (['photos', 'journal_share'] as $p) {
                if (Consents::has($patient, $p)) {
                    Consents::revoke($patient, $p);
                }
            }
            $dsr = new DataSubjectRequest(['patient_id' => $patient->id, 'type' => 'eliminacao', 'due_at' => now()->addDays((int) config('bioma.dsr_days'))]);
            $dsr->details = 'Conta encerrada pelo paciente no app.';
            $dsr->save();
            $patient->update(['status' => 'closing']);
            $user->forceFill(['status' => 'closed', 'password' => null])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            Audit::log('privacy.account_closed', 'patient:'.$patient->id, $patient->id);
            foreach (Professional::where('role', 'master')->pluck('user_id') as $uid) {
                AppNotification::send($uid, 'user-xmark', 'Um paciente encerrou a conta. Há um pedido de eliminação para tratar.', route('pro.requests'));
            }
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sua conta foi encerrada. Fotos e desabafos privados foram apagados.');
    }
}
