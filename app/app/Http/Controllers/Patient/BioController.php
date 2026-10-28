<?php

namespace App\Http\Controllers\Patient;

use App\Domain\AlertEngine;
use App\Domain\Files;
use App\Domain\Indicators;
use App\Domain\PatientData;
use App\Http\Controllers\Controller;
use App\Models\BioRecord;
use App\Models\StoredFile;
use App\Security\Audit;
use App\Support\Clock;
use App\Support\Fmt;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BioController extends Controller
{
    public function index()
    {
        return view('patient.bio-index', ['bio' => (new PatientData($this->patient()))->bio()->reverse()->values()]);
    }

    public function create()
    {
        return view('patient.bio-create', ['last' => (new PatientData($this->patient()))->bio()->last()]);
    }

    public function store(Request $request)
    {
        $rules = ['measured_on' => ['required', 'string', 'max:10'], 'laudo' => ['nullable', 'file']];
        foreach (Indicators::ALL as $k => $ind) {
            $rules["v.$k"] = ['required', 'string', 'max:12'];
        }
        $request->validate($rules, ['v.*.required' => 'Campo obrigatório.', 'measured_on.required' => 'Digite a data da medição.']);

        $values = self::parseValues($request->input('v', []));
        $date = self::parseDay($request->input('measured_on'), 'measured_on');
        $patient = $this->patient();

        $record = DB::transaction(function () use ($request, $patient, $values, $date) {
            $file = $request->hasFile('laudo') ? Files::store($request->file('laudo'), $patient, 'laudo', ['taken_on' => $date->toDateString(), 'label' => 'Bioimpedância'], 'laudo') : null;
            $r = new BioRecord(['patient_id' => $patient->id, 'measured_on' => $date->toDateString(), 'file_id' => $file?->id, 'created_by' => $this->user()->id]);
            $r->values = $values;
            $r->save();

            return $r;
        });
        AlertEngine::onBio($record);

        return redirect()->route('bio.done', $record);
    }

    public function show(BioRecord $record)
    {
        $this->own($record);
        [$cur, $prev] = $this->withPrevious($record);

        return view('patient.bio-show', ['record' => $record, 'cur' => $cur, 'prev' => $prev, 'file' => $record->file_id ? StoredFile::find($record->file_id) : null, 'lead' => Settings::leadName()]);
    }

    public function done(BioRecord $record)
    {
        $this->own($record);
        [$cur, $prev] = $this->withPrevious($record);

        return view('patient.bio-done', ['cur' => $cur, 'prev' => $prev, 'lead' => Settings::leadName()]);
    }

    public function destroy(BioRecord $record)
    {
        $this->own($record);
        AlertEngine::withdraw('bio', $record->id);
        if ($record->file_id && ($file = StoredFile::find($record->file_id))) {
            $file->delete();
        }
        $record->delete();
        Audit::log('bio.delete', 'bio:'.$record->id, $record->patient_id);

        return redirect()->route('bio.index')->with('status', 'Registro excluído.');
    }

    private function withPrevious(BioRecord $record): array
    {
        $bio = (new PatientData($this->patient()))->bio();
        $idx = $bio->search(fn ($b) => $b['id'] === $record->id);

        return [$bio[$idx], $idx > 0 ? $bio[$idx - 1] : null];
    }

    /** Converte "78,8" / "1.561" / "78.8" e confere a faixa plausível de cada indicador. */
    public static function parseValues(array $input): array
    {
        $values = [];
        $errors = [];
        foreach (Indicators::ALL as $k => $ind) {
            $n = Fmt::parseNumber($input[$k] ?? null);
            if ($n === null || $n < $ind['min'] || $n > $ind['max']) {
                $errors["v.$k"] = 'Confira o valor. Esperado entre '.Fmt::int($ind['min']).' e '.Fmt::int($ind['max']).' '.$ind['u'].'.';

                continue;
            }
            $values[$k] = $ind['dec'] === 0 ? (int) round($n) : round($n, 1);
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $values;
    }

    public static function parseDay(?string $value, string $field)
    {
        $d = Clock::parseDate($value);
        if (! $d || $d->greaterThan(Clock::today()) || $d->year < 2000) {
            throw ValidationException::withMessages([$field => 'Use uma data válida no formato dd/mm/aaaa, até hoje.']);
        }

        return $d;
    }
}
