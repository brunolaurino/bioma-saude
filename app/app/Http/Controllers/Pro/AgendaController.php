<?php

namespace App\Http\Controllers\Pro;

use App\Domain\Catalog;
use App\Domain\ProData;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Security\Access;
use App\Security\Audit;
use App\Security\Modules;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgendaController extends Controller
{
    public function index()
    {
        abort_unless(Modules::enabled('agenda'), 403, 'A agenda está desligada neste consultório.');
        $data = new ProData($this->user());
        $patients = $data->patients();
        $today = Clock::today();
        $days = collect(range(0, 4))->map(fn ($i) => $today->addDays($i));

        $items = Appointment::whereIn('patient_id', $patients->pluck('id'))
            ->whereBetween('starts_at', [$today->startOfDay()->utc(), $today->addDays(4)->endOfDay()->utc()])
            ->where('status', '!=', 'cancelada')->orderBy('starts_at')->get()
            ->groupBy(fn ($a) => Clock::local($a->starts_at)->toDateString());

        return view('pro.agenda', [
            'days' => $days, 'items' => $items, 'names' => $patients->keyBy('id')->map(fn ($p) => $p->name),
            'patients' => $patients->where('status', 'active')->values(), 'types' => Catalog::APPOINTMENT_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Modules::enabled('agenda'), 403);
        $data = $request->validate([
            'patient_id' => ['required', 'string'],
            'day' => ['required', 'string', 'max:10'],
            'time' => ['required', 'regex:/^\d{1,2}:\d{2}$/'],
            'type' => ['required', Rule::in(Catalog::APPOINTMENT_TYPES)],
        ], ['patient_id.required' => 'Escolha o paciente.', 'time.*' => 'Use o horário no formato 14:30.']);

        $patient = Patient::findOrFail($data['patient_id']);
        Access::authorizeView($this->user(), $patient);
        $day = Clock::parseDate($data['day']);
        if (! $day || $day->lt(Clock::today())) {
            return back()->withErrors(['day' => 'Use uma data a partir de hoje, no formato dd/mm/aaaa.'])->withInput();
        }
        $at = CarbonImmutable::parse($day->toDateString().' '.$data['time'], config('bioma.timezone'))->utc();
        $a = Appointment::create(['patient_id' => $patient->id, 'professional_id' => $this->user()->id, 'starts_at' => $at, 'type' => $data['type'], 'status' => 'agendada']);
        Audit::log('agenda.create', 'appointment:'.$a->id, $patient->id);

        return redirect()->route('pro.agenda')->with('status', 'Consulta marcada.');
    }

    public function status(Request $request, Appointment $appointment)
    {
        Access::authorizeView($this->user(), $appointment->patient);
        $request->validate(['status' => ['required', Rule::in(['atendida', 'cancelada', 'agendada'])]]);
        $appointment->update(['status' => $request->input('status')]);
        Audit::log('agenda.'.$request->input('status'), 'appointment:'.$appointment->id, $appointment->patient_id);

        return back()->with('status', match ($request->input('status')) { 'atendida' => 'Consulta marcada como atendida.', 'cancelada' => 'Consulta cancelada.', default => 'Consulta reaberta.' });
    }
}
