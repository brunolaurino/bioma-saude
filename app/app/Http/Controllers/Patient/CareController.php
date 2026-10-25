<?php

namespace App\Http\Controllers\Patient;

use App\Domain\Files;
use App\Domain\PatientData;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\StoredFile;
use App\Models\TherapySession;
use App\Security\Modules;
use App\Support\Clock;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Meu cuidado: nutrição, atividade física e saúde mental, conforme os módulos ligados. */
class CareController extends Controller
{
    public function index()
    {
        $patient = $this->patient();
        $data = new PatientData($patient);
        $m = Modules::map($patient->id);
        $today = Clock::today();
        $lead = Settings::leadName();

        $cards = [];
        if ($m['nutricao']) {
            $plan = StoredFile::of($patient)->where('kind', 'plano')->where('is_current', true)->first();
            $next = $m['agenda'] ? Appointment::of($patient)->where('status', 'agendada')->where('starts_at', '>=', now())->orderBy('starts_at')->first() : null;
            $cards[] = ['route' => 'nutrition', 'icon' => 'apple-whole', 't' => 'Nutrição', 'who' => $data->teamBy('Nutrição') ?? $lead, 'lines' => array_filter([
                $next ? 'Consulta '.\App\Support\Fmt::rel(Clock::local($next->starts_at)).' às '.\App\Support\Fmt::time(Clock::local($next->starts_at)) : null,
                $plan ? 'Plano alimentar de '.\App\Support\Fmt::short($plan->taken_on) : 'Nenhum plano alimentar anexado',
            ])];
        }
        if ($m['atividade']) {
            $week = $data->workouts()->filter(fn ($w) => $w->day->greaterThan($today->subDays(7)));
            $cards[] = ['route' => 'activity', 'icon' => 'person-running', 't' => 'Atividade física', 'who' => $data->teamBy('Educação física') ?? $lead, 'lines' => [
                $week->count().' '.\App\Support\Fmt::plural($week->count(), 'treino', 'treinos').' nos últimos 7 dias',
                $week->where('category', 'Força')->count().' de força · '.$week->where('category', 'Aeróbico')->count().' aeróbicos',
            ]];
        }
        if ($m['saude_mental']) {
            $next = TherapySession::of($patient)->where('status', 'agendada')->where('starts_at', '>=', $today->startOfDay()->utc())->orderBy('starts_at')->first();
            $last = $data->journal(false)->first();
            $cards[] = ['route' => 'mental', 'icon' => 'brain', 't' => 'Saúde mental', 'who' => $data->teamBy('Psicologia') ?? $lead, 'lines' => [
                $next ? 'Próxima sessão: '.\App\Support\Fmt::wd(Clock::local($next->starts_at)).', '.\App\Support\Fmt::short(Clock::local($next->starts_at)) : 'Sem sessão agendada',
                $last ? 'Último desabafo '.\App\Support\Fmt::rel($last->day) : 'Nenhum desabafo ainda',
            ]];
        }

        return view('patient.care', ['cards' => $cards, 'm' => $m]);
    }

    public function nutrition()
    {
        $patient = $this->patient();
        $data = new PatientData($patient);

        return view('patient.nutrition', self::nutritionData($patient) + ['who' => $data->teamBy('Nutrição') ?? Settings::leadName()]);
    }

    /** Plano vigente, versões anteriores e consultas. Também usado no prontuário. */
    public static function nutritionData($patient): array
    {
        $plans = StoredFile::of($patient)->where('kind', 'plano')->orderByDesc('is_current')->orderByDesc('taken_on')->orderByDesc('created_at')->get();
        $consults = Consultation::of($patient)->orderByDesc('day')->get();
        $upcoming = Modules::enabled('agenda', $patient->id)
            ? Appointment::of($patient)->where('status', 'agendada')->where('starts_at', '>=', Clock::today()->startOfDay()->utc())->orderBy('starts_at')->get()
            : collect();
        $names = \App\Models\Professional::pluck('name', 'user_id');

        return ['current' => $plans->firstWhere('is_current', true), 'old' => $plans->where('is_current', false)->values(), 'consults' => $consults, 'upcoming' => $upcoming, 'names' => $names];
    }

    public function uploadPlan(Request $request)
    {
        $request->validate(['arquivo' => ['required', 'file']], ['arquivo.required' => 'Escolha o PDF do plano.']);
        self::storePlan($this->patient(), $request);

        return redirect()->route('nutrition')->with('status', 'Plano alimentar anexado.');
    }

    public static function storePlan($patient, Request $request): void
    {
        DB::transaction(function () use ($patient, $request) {
            StoredFile::of($patient)->where('kind', 'plano')->update(['is_current' => false]);
            Files::store($request->file('arquivo'), $patient, 'plano', ['taken_on' => Clock::todayIso(), 'is_current' => true]);
        });
    }
}
