<?php

namespace App\Domain;

use App\Models\Alert;
use App\Models\JournalEntry;
use App\Models\Patient;
use App\Models\User;
use App\Security\Access;
use App\Security\Modules;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Visão do profissional: só pacientes e módulos liberados para ele (o master vê tudo). */
class ProData
{
    public function __construct(public readonly User $user)
    {
    }

    /** @return Collection<int, Patient> pacientes visíveis, sem os encerrados */
    public function patients(bool $withInvited = true): Collection
    {
        return Access::patients($this->user)->with('goals')
            ->whereIn('status', $withInvited ? ['active', 'invited'] : ['active'])->get()
            ->sortBy(fn ($p) => mb_strtolower((string) $p->name))->values();
    }

    public function canSee(Patient $p, string $module): bool
    {
        return Access::canView($this->user, $p, $module);
    }

    /** Alertas abertos que este profissional pode ver (pela regra → módulo). */
    public function openAlerts(?Collection $patients = null): Collection
    {
        $patients ??= $this->patients();
        $byId = $patients->keyBy('id');

        return Alert::whereIn('patient_id', $byId->keys())->whereNull('resolved_at')->orderByDesc('day')->get()
            ->filter(fn ($a) => $this->canSee($byId[$a->patient_id], AlertEngine::RULES[$a->rule]['module'] ?? 'sintomas'))
            ->values();
    }

    /** Desabafos compartilhados ainda não lidos por este profissional. */
    public function unreadJournals(?Collection $patients = null): Collection
    {
        $patients ??= $this->patients();
        $ids = $patients->filter(fn ($p) => $this->canSee($p, 'saude_mental'))->pluck('id');
        $read = DB::table('journal_reads')->where('professional_id', $this->user->id)->pluck('journal_id');

        return JournalEntry::whereIn('patient_id', $ids)->where('shared', true)->whereNotIn('id', $read)->orderByDesc('day')->get();
    }

    /** Linha da lista de pacientes: peso, gordura, adesão, último registro, alertas e desabafos novos. */
    public function row(Patient $p, Collection $alerts, Collection $unread): array
    {
        $data = new PatientData($p);
        $bio = $this->canSee($p, 'bioimpedancia') ? $data->bio() : collect();
        $last = $bio->last();
        $prev = $bio->count() > 1 ? $bio[$bio->count() - 2] : null;
        $lastDay = $data->lastRecordDay();
        $lastDays = $lastDay ? (int) $lastDay->diffInDays(\App\Support\Clock::today()) : null;
        $nAlerts = $alerts->where('patient_id', $p->id)->count();
        $idle = AlertEngine::enabled('idle') ? AlertEngine::idleDays() : PHP_INT_MAX;

        $status = match (true) {
            $p->status === 'invited' => ['tone' => null, 'l' => 'Convidado'],
            $nAlerts > 0 => ['tone' => 'bad', 'l' => 'Alerta'],
            $lastDays === null || $lastDays >= $idle => ['tone' => 'warn', 'l' => 'Sem registro'],
            default => ['tone' => 'ok', 'l' => 'Em dia'],
        };

        return [
            'p' => $p, 'w' => $last['w'] ?? null, 'dw' => $last && $prev ? $last['w'] - $prev['w'] : null, 'f' => $last['f'] ?? null,
            'adh' => $this->canSee($p, 'metas') && Modules::enabled('metas', $p->id) ? $data->adherence() : null,
            'last' => $lastDay, 'lastDays' => $lastDays, 'alerts' => $nAlerts, 'unread' => $unread->where('patient_id', $p->id)->count(),
            'status' => $status, 'idle' => $p->status === 'active' && ($lastDays === null || $lastDays >= $idle),
        ];
    }

    public static function adhTone(?int $adh): ?string
    {
        return $adh === null ? null : ($adh >= 75 ? 'ok' : ($adh >= 55 ? 'warn' : 'bad'));
    }
}
