<?php

namespace App\Http\Controllers\Patient;

use App\Domain\Catalog;
use App\Domain\PatientData;
use App\Http\Controllers\Controller;
use App\Models\Workout;
use App\Support\Clock;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    public function index()
    {
        $data = new PatientData($this->patient());
        $all = $data->workouts();
        $today = Clock::today();

        return view('patient.activity', [
            'week' => collect(range(6, 0))->map(fn ($i) => ['d' => $today->subDays($i), 'w' => $all->filter(fn ($w) => $w->day->equalTo($today->subDays($i)))->first()]),
            'last7' => $all->filter(fn ($w) => $w->day->greaterThan($today->subDays(7))),
            'today' => $all->filter(fn ($w) => $w->day->equalTo($today)),
            'history' => $all->reject(fn ($w) => $w->day->equalTo($today))->take(60),
            'who' => $data->teamBy('Educação física') ?? Settings::leadName(),
        ]);
    }

    public function create(Request $request)
    {
        $cat = $request->query('categoria') === 'Aeróbico' ? 'Aeróbico' : 'Força';

        return view('patient.workout-create', ['cat' => $cat]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(Catalog::MODALITIES))],
            'modality' => ['required', 'string'],
            'minutes' => ['required', 'integer', 'between:5,600'],
            'intensity' => ['required', Rule::in(Catalog::WORKOUT_INTENSITY)],
            'note' => ['nullable', 'string', 'max:500'],
        ], ['minutes.*' => 'Informe a duração entre 5 e 600 minutos.']);
        abort_unless(in_array($data['modality'], Catalog::MODALITIES[$data['category']], true), 422);

        $w = new Workout(['patient_id' => $this->patient()->id, 'day' => Clock::todayIso()] + collect($data)->except('note')->all());
        $w->note = trim((string) ($data['note'] ?? '')) ?: null;
        $w->save();

        return redirect()->route('activity')->with('status', 'Treino registrado.');
    }
}
