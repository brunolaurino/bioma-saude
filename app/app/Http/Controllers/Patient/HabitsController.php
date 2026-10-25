<?php

namespace App\Http\Controllers\Patient;

use App\Domain\Catalog;
use App\Domain\PatientData;
use App\Http\Controllers\Controller;
use App\Models\SymptomEntry;
use App\Security\Modules;
use App\Support\Clock;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Metas diárias. Só o dia de hoje pode ser editado; os 6 dias anteriores aparecem como resumo. */
class HabitsController extends Controller
{
    public function index(Request $request)
    {
        $patient = $this->patient();
        $data = new PatientData($patient);
        $today = Clock::today();
        $offset = max(0, min(6, (int) $request->query('dia', 0)));
        $selected = $today->subDays($offset);
        $entries = $data->entriesBetween($today->subDays(6), $today);

        $week = collect(range(6, 0))->map(fn ($i) => ['offset' => $i, 'date' => $today->subDays($i), 'done' => $data->doneCount($data->day($today->subDays($i), $entries))]);
        $symptoms = Modules::enabled('sintomas', $patient->id) ? $data->symptomsBetween($selected, $selected) : collect();

        return view('patient.habits', [
            'm' => Modules::map($patient->id),
            'goalOn' => collect(Modules::GOALS)->map(fn ($l, $g) => Modules::goalEnabled($g))->all(),
            'offset' => $offset,
            'selected' => $selected,
            'week' => $week,
            'total' => count($data->activeGoals()),
            'day' => $data->day($selected, $entries),
            'goals' => $data->goals(),
            'symptoms' => $symptoms,
            'grid' => $data->habitGrid(),
            'lead' => Settings::leadName(),
        ]);
    }

    public function water(Request $request)
    {
        abort_unless(Modules::goalEnabled('water'), 403);
        $request->validate(['add' => ['nullable', 'integer', 'between:-1000,2000'], 'set' => ['nullable', 'integer', 'between:0,10000']]);
        $patient = $this->patient();
        $current = (new PatientData($patient))->day(Clock::today())['water'];
        $value = $request->filled('set') ? (int) $request->input('set') : $current + (int) $request->input('add', 0);
        PatientData::setEntry($patient, 'water', max(0, min(10000, $value)));

        return back()->withFragment('agua');
    }

    public function protein(Request $request)
    {
        abort_unless(Modules::goalEnabled('protein'), 403);
        $request->validate(['value' => ['required', Rule::in(['1', '0', 'clear'])]]);
        $v = $request->input('value');
        PatientData::setEntry($this->patient(), 'protein', $v === 'clear' ? null : $v === '1');

        return back()->withFragment('proteina');
    }

    public function fvl(Request $request)
    {
        abort_unless(Modules::goalEnabled('fvl'), 403);
        $request->validate(['item' => ['required', Rule::in(array_keys(Catalog::FVL))]]);
        $patient = $this->patient();
        $item = $request->input('item');
        $on = (new PatientData($patient))->day(Clock::today())['fvl'][$item];
        PatientData::setEntry($patient, $item, ! $on);

        return back()->withFragment('vegetais');
    }
}
