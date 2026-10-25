<?php

namespace App\Domain;

use App\Models\Alert;
use App\Models\BioRecord;
use App\Models\GoalEntry;
use App\Models\JournalEntry;
use App\Models\Patient;
use App\Models\SymptomEntry;
use App\Models\Workout;
use App\Security\Modules;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Lê e resume os dados de um paciente (decifrando no PHP, o que é viável no volume de um
 * consultório). Usado pelas telas do paciente e pelo prontuário do profissional.
 */
class PatientData
{
    private ?Collection $bio = null;

    private array $entries = [];

    public function __construct(public readonly Patient $patient)
    {
        $patient->loadMissing('goals');
    }

    /** @return Collection<int, array{id:string,d:CarbonImmutable,w:float,f:float,m:float,v:float,t:float,file_id:?string}> */
    public function bio(): Collection
    {
        return $this->bio ??= BioRecord::of($this->patient)->orderBy('measured_on')->orderBy('created_at')->get()
            ->map(fn (BioRecord $r) => ['id' => $r->id, 'd' => $r->measured_on, 'file_id' => $r->file_id] + array_map('floatval', (array) $r->values))
            ->values();
    }

    public function goals(): array
    {
        return [
            'weight' => $this->patient->goal('weight') !== null ? (float) $this->patient->goal('weight') : null,
            'water' => (int) $this->patient->goal('water', 2500),
            'protein' => (int) $this->patient->goal('protein', 100),
        ];
    }

    /** Metas que contam no anel do dia, conforme o que o master ligou. */
    public function activeGoals(): array
    {
        $pid = $this->patient->id;
        $list = [];
        if (Modules::enabled('metas', $pid)) {
            foreach (array_keys(Modules::GOALS) as $g) {
                if (Modules::goalEnabled($g)) {
                    $list[] = $g;
                }
            }
        }
        if (Modules::enabled('sintomas', $pid)) {
            $list[] = 'feeling';
        }

        return $list;
    }

    /** Marcações de metas por dia, carregadas uma vez para o intervalo pedido. */
    public function entriesBetween(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $key = $from->toDateString().'|'.$to->toDateString();
        if (isset($this->entries[$key])) {
            return $this->entries[$key];
        }

        $days = [];
        foreach (GoalEntry::of($this->patient)->whereBetween('day', [$from->toDateString(), $to->toDateString()])->get() as $e) {
            $days[$e->day->toDateString()][$e->type] = $e->value;
        }

        return $this->entries[$key] = $days;
    }

    public function day(CarbonImmutable $day, ?array $entries = null): array
    {
        $entries ??= $this->entriesBetween($day, $day);
        $e = $entries[$day->toDateString()] ?? [];

        return [
            'water' => (int) ($e['water'] ?? 0),
            'protein' => array_key_exists('protein', $e) ? (bool) $e['protein'] : null,
            'fvl' => ['frutas' => (bool) ($e['frutas'] ?? false), 'verduras' => (bool) ($e['verduras'] ?? false), 'legumes' => (bool) ($e['legumes'] ?? false)],
            'feeling' => (bool) ($e['feeling'] ?? false),
        ];
    }

    /** Quantas metas ativas o dia bateu. */
    public function doneCount(array $day): int
    {
        $goals = $this->goals();
        $n = 0;
        foreach ($this->activeGoals() as $g) {
            $n += match ($g) {
                'water' => $day['water'] >= $goals['water'] ? 1 : 0,
                'protein' => $day['protein'] === true ? 1 : 0,
                'fvl' => count(array_filter($day['fvl'])) === 3 ? 1 : 0,
                'feeling' => $day['feeling'] ? 1 : 0,
                default => 0,
            };
        }

        return $n;
    }

    /** Dias seguidos com a meta de água batida, contando hoje se já bateu. */
    public function waterStreak(): int
    {
        $today = Clock::today();
        $entries = $this->entriesBetween($today->subDays(60), $today);
        $goal = $this->goals()['water'];
        $n = 0;
        $d = $today;
        if ($this->day($d, $entries)['water'] < $goal) {
            $d = $d->subDay();
        }
        while ($this->day($d, $entries)['water'] >= $goal && $n < 60) {
            $n++;
            $d = $d->subDay();
        }

        return $n;
    }

    /** Adesão às metas nos últimos 14 dias (inclui hoje), em %. */
    public function adherence(int $days = 14): int
    {
        $goals = array_values(array_diff($this->activeGoals(), ['feeling']));
        if (! $goals) {
            return 0;
        }
        $today = Clock::today();
        $entries = $this->entriesBetween($today->subDays($days - 1), $today);
        $sum = 0;
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $this->day($today->subDays($i), $entries);
            foreach ($goals as $g) {
                $sum += match ($g) {
                    'water' => $day['water'] >= $this->goals()['water'] ? 1 : 0,
                    'protein' => $day['protein'] === true ? 1 : 0,
                    'fvl' => count(array_filter($day['fvl'])) === 3 ? 1 : 0,
                };
            }
        }

        return (int) round($sum / ($days * count($goals)) * 100);
    }

    /** Linhas da grade de 14 dias. Valores: 1 meta batida, 0.5 parcial, 0 não bateu, -0.5 sintoma leve, -1 moderado ou forte. */
    public function habitGrid(bool $withWorkouts = false): array
    {
        $today = Clock::today();
        $from = $today->subDays(13);
        $entries = $this->entriesBetween($from, $today);
        $goals = $this->goals();
        $symptoms = $this->symptomsBetween($from, $today)->groupBy(fn ($s) => $s->day->toDateString());
        $days = collect(range(13, 0))->map(fn ($i) => $today->subDays($i));
        $pid = $this->patient->id;

        $rows = [];
        if (Modules::enabled('metas', $pid)) {
            if (Modules::goalEnabled('water')) {
                $rows[] = ['l' => 'Água', 'ic' => 'glass-water', 'vals' => $days->map(fn ($d) => $this->day($d, $entries)['water'] >= $goals['water'] ? 1 : 0)->all()];
            }
            if (Modules::goalEnabled('protein')) {
                $rows[] = ['l' => 'Proteína', 'ic' => 'drumstick-bite', 'vals' => $days->map(fn ($d) => $this->day($d, $entries)['protein'] === true ? 1 : 0)->all()];
            }
            if (Modules::goalEnabled('fvl')) {
                $rows[] = ['l' => 'Frutas/verd.', 'ic' => 'carrot', 'vals' => $days->map(function ($d) use ($entries) {
                    $n = count(array_filter($this->day($d, $entries)['fvl']));

                    return $n === 3 ? 1 : ($n > 0 ? 0.5 : 0);
                })->all()];
            }
        }
        if (Modules::enabled('sintomas', $pid)) {
            $rows[] = ['l' => 'Sintomas', 'ic' => 'notes-medical', 'sym' => true, 'vals' => $days->map(function ($d) use ($symptoms, $entries) {
                $list = $symptoms->get($d->toDateString(), collect());
                if ($list->contains(fn ($s) => $s->intensity !== 'Leve')) {
                    return -1;
                }
                if ($list->isNotEmpty()) {
                    return -0.5;
                }

                return $this->day($d, $entries)['feeling'] ? 1 : null;
            })->all()];
        }
        if ($withWorkouts && Modules::enabled('atividade', $pid)) {
            $w = $this->workouts()->groupBy(fn ($x) => $x->day->toDateString());
            $rows[] = ['l' => 'Treino', 'ic' => 'dumbbell', 'vals' => $days->map(fn ($d) => $w->has($d->toDateString()) ? 1 : 0)->all()];
        }

        return ['rows' => $rows, 'from' => $from];
    }

    /** Percentual de dias com meta batida em todo o histórico de 30 dias, por meta. */
    public function goalRate(string $goal, int $days = 30): int
    {
        $today = Clock::today();
        $entries = $this->entriesBetween($today->subDays($days - 1), $today);
        $goals = $this->goals();
        $hit = 0;
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $this->day($today->subDays($i), $entries);
            $hit += match ($goal) {
                'water' => $day['water'] >= $goals['water'] ? 1 : 0,
                'protein' => $day['protein'] === true ? 1 : 0,
                'fvl' => count(array_filter($day['fvl'])) === 3 ? 1 : 0,
            };
        }

        return (int) round($hit / $days * 100);
    }

    public function symptomsBetween(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return SymptomEntry::of($this->patient)->whereBetween('day', [$from->toDateString(), $to->toDateString()])->orderBy('day')->orderBy('created_at')->get();
    }

    public function symptoms(int $limit = 200): Collection
    {
        return SymptomEntry::of($this->patient)->orderByDesc('day')->orderByDesc('created_at')->limit($limit)->get();
    }

    public function todaySymptoms(): Collection
    {
        return SymptomEntry::of($this->patient)->where('day', Clock::todayIso())->orderBy('created_at')->get();
    }

    public function workouts(): Collection
    {
        return Workout::of($this->patient)->orderByDesc('day')->orderByDesc('created_at')->get();
    }

    /** Desabafos. $sharedOnly vale para a equipe: o privado nunca sai do servidor para o painel. */
    public function journal(bool $sharedOnly): Collection
    {
        return JournalEntry::of($this->patient)->when($sharedOnly, fn ($q) => $q->where('shared', true))
            ->orderByDesc('day')->orderByDesc('created_at')->get();
    }

    public function openAlerts(): Collection
    {
        return Alert::of($this->patient)->whereNull('resolved_at')->orderByDesc('day')->get();
    }

    /** Profissional da equipe por especialidade, para os textos do app ("Psic. Helena Costa"). */
    public function teamBy(string $specialty): ?string
    {
        $row = \App\Models\CareTeam::where('patient_id', $this->patient->id)->with('professional')->get()
            ->first(fn ($t) => $t->professional?->specialty === $specialty);

        return $row?->professional->name;
    }

    /** Grava (ou apaga, com null) uma marcação de meta do dia. Só o dia de hoje é editável pelo paciente. */
    public static function setEntry(Patient $patient, string $type, mixed $value, ?CarbonImmutable $day = null): void
    {
        $day = ($day ?? Clock::today())->toDateString();
        $entry = GoalEntry::of($patient)->where('day', $day)->where('type', $type)->first();
        if ($value === null) {
            $entry?->delete();

            return;
        }
        $entry ??= new GoalEntry(['patient_id' => $patient->id, 'day' => $day, 'type' => $type]);
        $entry->value = $value;
        $entry->save();
    }

    /** Data do registro mais recente de qualquer tipo, para "sem registro há N dias". */
    public function lastRecordDay(): ?CarbonImmutable
    {
        $dates = array_filter([
            GoalEntry::of($this->patient)->max('day'),
            SymptomEntry::of($this->patient)->max('day'),
            BioRecord::of($this->patient)->max('measured_on'),
            Workout::of($this->patient)->max('day'),
            JournalEntry::of($this->patient)->max('day'),
        ]);

        return $dates ? CarbonImmutable::parse(max($dates)) : null;
    }
}
