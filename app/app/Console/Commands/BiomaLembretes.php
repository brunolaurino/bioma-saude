<?php

namespace App\Console\Commands;

use App\Domain\Catalog;
use App\Models\AppNotification;
use App\Models\Patient;
use App\Models\TherapySession;
use App\Security\Modules;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Lembretes dentro do app, rodando a cada 15 minutos pelo agendador. O texto é sempre genérico:
 * nenhum lembrete revela dado de saúde, nem na tela bloqueada.
 */
class BiomaLembretes extends Command
{
    protected $signature = 'bioma:lembretes';

    protected $description = 'Envia os lembretes do app que estão no horário';

    private const TEXT = [
        'water' => ['glass-water', 'Hora de um copo de água.', 'habits'],
        'habits' => ['list-check', 'Que tal marcar as metas de hoje?', 'habits'],
        'bio' => ['weight-scale', 'Começo de mês: hora de registrar a bioimpedância.', 'bio.create'],
        'therapy' => ['brain', 'Você tem um compromisso daqui a pouco.', 'mental'],
        'workout' => ['dumbbell', 'Treinou hoje? Registre no app.', 'activity'],
        'journal' => ['feather', 'Que tal escrever como foi sua semana?', 'journal.create'],
    ];

    public function handle(): int
    {
        if (! Modules::enabled('lembretes')) {
            return self::SUCCESS;
        }
        $now = Clock::now();
        $sent = 0;

        foreach (Patient::where('status', 'active')->whereNotNull('user_id')->get() as $p) {
            if (! Modules::enabled('lembretes', $p->id)) {
                continue;
            }
            $prefs = DB::table('reminder_prefs')->where('patient_id', $p->id)->get()->keyBy('key');
            foreach (Catalog::REMINDERS as $key => $r) {
                $on = isset($prefs[$key]) ? (bool) $prefs[$key]->enabled : $r['default'];
                if (! $on || ! Modules::enabled($r['module'], $p->id)) {
                    continue;
                }
                $last = isset($prefs[$key]) && $prefs[$key]->last_sent_at ? Clock::local(CarbonImmutable::parse($prefs[$key]->last_sent_at, 'UTC')) : null;
                if ($this->due($key, $now, $last, $p)) {
                    [$icon, $text, $route] = self::TEXT[$key];
                    AppNotification::send($p->user_id, $icon, $text, route($route), $text);
                    DB::table('reminder_prefs')->updateOrInsert(['patient_id' => $p->id, 'key' => $key], ['enabled' => true, 'last_sent_at' => now(), 'updated_at' => now()]);
                    $sent++;
                }
            }
        }
        $this->info("$sent lembretes enviados.");

        return self::SUCCESS;
    }

    private function due(string $key, CarbonImmutable $now, ?CarbonImmutable $last, Patient $p): bool
    {
        $h = (int) $now->format('G');
        $today = fn () => $last && $last->isSameDay($now);

        return match ($key) {
            'water' => $h >= 8 && $h <= 20 && (! $last || $last->diffInMinutes($now) >= 115),
            'habits' => $now->format('H:i') >= '20:30' && ! $today(),
            'bio' => $now->day === 1 && $h >= 9 && ! $today(),
            'workout' => in_array($now->dayOfWeek, [1, 3, 5], true) && $h >= 19 && ! $today(),
            'journal' => $now->dayOfWeek === 0 && $h >= 18 && ! $today(),
            'therapy' => ($s = TherapySession::of($p)->where('status', 'agendada')->whereBetween('starts_at', [now(), now()->addMinutes(65)])->orderBy('starts_at')->first()) !== null
                && (! $last || $last->lt(Clock::local($s->starts_at)->subMinutes(70))),
            default => false,
        };
    }
}
