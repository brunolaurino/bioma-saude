<?php

namespace App\Domain;

use App\Models\Alert;
use App\Models\BioRecord;
use App\Models\JournalEntry;
use App\Models\SymptomEntry;
use App\Security\Modules;
use App\Support\Fmt;
use Illuminate\Support\Facades\DB;

/**
 * Regras de alerta do protótipo, ligadas e desligadas pelo master. Os alertas só avisam a
 * equipe; não tomam decisão sobre o paciente (por isso o art. 20 da LGPD não se aplica).
 */
class AlertEngine
{
    public const RULES = [
        'strong' => ['icon' => 'triangle-exclamation', 'l' => 'Sintoma forte gera alerta vermelho', 'h' => 'Aviso imediato no painel', 'module' => 'sintomas'],
        'moderate' => ['icon' => 'circle-exclamation', 'l' => 'Sintoma moderado gera alerta amarelo', 'h' => 'Aparece em Alertas abertos', 'module' => 'sintomas'],
        'repeat' => ['icon' => 'repeat', 'l' => 'Mesmo sintoma por 3 dias seguidos', 'h' => 'Vira alerta amarelo, mesmo se for leve', 'module' => 'sintomas'],
        'mood' => ['icon' => 'face-frown', 'l' => 'Desabafo com humor "muito mal"', 'h' => 'Alerta amarelo quando o desabafo é compartilhado', 'module' => 'saude_mental'],
        'weight' => ['icon' => 'weight-scale', 'l' => 'Peso subiu mais de 1 kg no mês', 'h' => 'Alerta amarelo no prontuário', 'module' => 'bioimpedancia'],
    ];

    public const IDLE_OPTIONS = [3, 5, 7, 10, 14];

    private static ?array $rules = null;

    public static function enabled(string $rule): bool
    {
        self::$rules ??= DB::table('alert_rules')->pluck('enabled', 'key')->map(fn ($v) => (bool) $v)->all();

        return self::$rules[$rule] ?? true;
    }

    public static function idleDays(): int
    {
        $params = json_decode((string) DB::table('alert_rules')->where('key', 'idle')->value('params'), true);

        return (int) ($params['days'] ?? 7);
    }

    public static function flush(): void
    {
        self::$rules = null;
    }

    public static function onSymptom(SymptomEntry $s): void
    {
        $p = $s->patient_id;
        if (! Modules::enabled('sintomas', $p)) {
            return;
        }

        $sev = ['Forte' => 'bad', 'Moderada' => 'warn'][$s->intensity] ?? null;
        $rule = ['Forte' => 'strong', 'Moderada' => 'moderate'][$s->intensity] ?? null;
        if ($rule && self::enabled($rule)) {
            self::raise($p, $rule, $sev, 'symptom', $s->id, "{$s->symptom} ({$s->intensity})", $s->day->toDateString());
        }

        if (self::enabled('repeat')) {
            $days = SymptomEntry::of($p)->whereBetween('day', [$s->day->subDays(2)->toDateString(), $s->day->toDateString()])->get()
                ->filter(fn ($x) => $x->symptom === $s->symptom)->map(fn ($x) => $x->day->toDateString())->unique();
            if ($days->count() >= 3) {
                self::raise($p, 'repeat', 'warn', 'symptom', $s->id, "{$s->symptom} por 3 dias seguidos", $s->day->toDateString());
            }
        }
    }

    public static function onJournal(JournalEntry $j): void
    {
        if ($j->shared && (int) $j->mood === 1 && self::enabled('mood') && Modules::enabled('saude_mental', $j->patient_id)) {
            self::raise($j->patient_id, 'mood', 'warn', 'journal', $j->id, 'Desabafo com humor muito mal', $j->day->toDateString());
        }
    }

    public static function onBio(BioRecord $r): void
    {
        if (! self::enabled('weight') || ! Modules::enabled('bioimpedancia', $r->patient_id)) {
            return;
        }
        $prev = BioRecord::of($r->patient_id)->where('id', '!=', $r->id)
            ->whereBetween('measured_on', [$r->measured_on->subDays(31)->toDateString(), $r->measured_on->toDateString()])
            ->orderBy('measured_on')->get()->first();
        if ($prev && ($r->values['w'] - $prev->values['w']) > 1.0) {
            self::raise($r->patient_id, 'weight', 'warn', 'bio', $r->id, 'Peso subiu '.Fmt::num($r->values['w'] - $prev->values['w']).' kg no mês', $r->measured_on->toDateString());
        }
    }

    private static function raise(string $patientId, string $rule, string $severity, string $type, string $sourceId, string $summary, string $day): void
    {
        if (Alert::where(['rule' => $rule, 'source_type' => $type, 'source_id' => $sourceId])->exists()) {
            return;
        }

        Alert::create([
            'patient_id' => $patientId, 'rule' => $rule, 'severity' => $severity, 'source_type' => $type,
            'source_id' => $sourceId, 'summary' => $summary, 'day' => $day,
        ]);
    }

    /** Quando o paciente apaga ou altera o registro de origem, alertas abertos dele somem da fila. */
    public static function withdraw(string $type, string $sourceId): void
    {
        Alert::where(['source_type' => $type, 'source_id' => $sourceId])->whereNull('resolved_at')->delete();
    }
}
