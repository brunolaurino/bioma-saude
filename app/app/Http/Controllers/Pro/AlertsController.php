<?php

namespace App\Http\Controllers\Pro;

use App\Domain\AlertEngine;
use App\Domain\ProData;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Security\Access;
use App\Security\Audit;
use App\Support\Clock;
use Illuminate\Http\Request;

class AlertsController extends Controller
{
    public const VIEWS = ['abertos' => 'Abertos', 'revisados' => 'Revisados', 'todos' => 'Todos'];

    public const SEVERITIES = ['todos' => 'Toda gravidade', 'bad' => 'Alerta vermelho', 'warn' => 'Atenção'];

    public function index(Request $request)
    {
        $data = new ProData($this->user());
        $patients = $data->patients();
        $byId = $patients->keyBy('id');
        $view = array_key_exists($request->query('ver'), self::VIEWS) ? $request->query('ver') : 'abertos';
        $sev = array_key_exists($request->query('gravidade'), self::SEVERITIES) ? $request->query('gravidade') : 'todos';

        $all = Alert::whereIn('patient_id', $byId->keys())->where('day', '>=', Clock::today()->subDays(90)->toDateString())
            ->orderByDesc('day')->orderByDesc('created_at')->get()
            ->filter(fn ($a) => $data->canSee($byId[$a->patient_id], AlertEngine::RULES[$a->rule]['module'] ?? 'sintomas'))->values();

        $list = $all->filter(fn ($a) => match ($view) { 'abertos' => $a->resolved_at === null, 'revisados' => $a->resolved_at !== null, default => true })
            ->filter(fn ($a) => $sev === 'todos' || $a->severity === $sev)->values();

        $since = Clock::today()->subDays(29);
        $freq = collect();
        foreach ($patients as $p) {
            if ($data->canSee($p, 'sintomas')) {
                $freq = $freq->merge((new \App\Domain\PatientData($p))->symptomsBetween($since, Clock::today()));
            }
        }
        $freq = $freq->groupBy('symptom')->map(fn ($g, $l) => ['l' => $l, 'v' => $g->count(), 'tone' => $g->contains('intensity', 'Forte') ? 'bad' : ($g->contains('intensity', 'Moderada') ? 'warn' : 'ok')])->sortByDesc('v')->values();

        return view('pro.alerts', [
            'list' => $list, 'view' => $view, 'sev' => $sev, 'names' => $byId->map(fn ($p) => $p->name),
            'openCount' => $all->whereNull('resolved_at')->count(), 'freq' => $freq,
        ]);
    }

    public function resolve(Alert $alert)
    {
        $patient = $alert->patient;
        Access::authorizeView($this->user(), $patient, AlertEngine::RULES[$alert->rule]['module'] ?? 'sintomas');
        if ($alert->resolved_at === null) {
            $alert->forceFill(['resolved_by' => $this->user()->id, 'resolved_at' => now()])->save();
            Audit::log('alert.resolve', 'alert:'.$alert->id, $patient->id, ['rule' => $alert->rule]);
        }

        return back()->with('status', 'Alerta marcado como revisado.');
    }
}
