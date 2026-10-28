<?php

namespace App\Http\Controllers\Patient;

use App\Domain\AlertEngine;
use App\Domain\Catalog;
use App\Domain\PatientData;
use App\Http\Controllers\Controller;
use App\Models\SymptomEntry;
use App\Security\Audit;
use App\Support\Clock;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SymptomsController extends Controller
{
    public function index()
    {
        $patient = $this->patient();
        $data = new PatientData($patient);
        $today = $data->todaySymptoms();

        return view('patient.symptoms', [
            'today' => $today->keyBy('symptom'),
            'note' => $today->first()?->note,
            'past' => $data->symptoms(40)->reject(fn ($s) => $s->day->equalTo(Clock::today()))->take(8),
            'lead' => Settings::leadName(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            's' => ['required', 'array', 'min:1'],
            's.*' => ['integer', 'between:0,'.(count(Catalog::SYMPTOMS) - 1)],
            'i' => ['nullable', 'array'],
            'i.*' => [Rule::in(Catalog::INTENSITY)],
            'note' => ['nullable', 'string', 'max:1000'],
        ], ['s.required' => 'Escolha pelo menos um sintoma, ou toque em "Estou bem hoje".']);

        $patient = $this->patient();
        $chosen = [];
        foreach (array_unique($data['s']) as $idx) {
            $chosen[Catalog::SYMPTOMS[$idx]] = $data['i'][$idx] ?? 'Leve';
        }
        $note = trim((string) ($data['note'] ?? '')) ?: null;

        DB::transaction(function () use ($patient, $chosen, $note) {
            $existing = (new PatientData($patient))->todaySymptoms()->keyBy('symptom');

            foreach ($existing as $name => $entry) {
                if (! isset($chosen[$name])) {
                    AlertEngine::withdraw('symptom', $entry->id);
                    $entry->delete();
                }
            }

            $first = true;
            foreach ($chosen as $name => $intensity) {
                $entry = $existing[$name] ?? new SymptomEntry(['patient_id' => $patient->id, 'day' => Clock::todayIso()]);
                $changed = ! $entry->exists || $entry->intensity !== $intensity;
                if ($entry->exists && $changed) {
                    AlertEngine::withdraw('symptom', $entry->id);
                }
                $entry->symptom = $name;
                $entry->intensity = $intensity;
                $entry->note = $first ? $note : null;
                $entry->save();
                $first = false;
                if ($changed) {
                    AlertEngine::onSymptom($entry);
                }
            }

            PatientData::setEntry($patient, 'feeling', true);
        });

        return redirect()->route('habits')->with('status', 'Sintomas salvos. '.Settings::leadName().' vai ver antes da consulta.');
    }

    public function fine()
    {
        $patient = $this->patient();
        if ((new PatientData($patient))->todaySymptoms()->isNotEmpty()) {
            return back()->with('error', 'Você já registrou sintomas hoje. Remova-os para marcar que está bem.');
        }
        PatientData::setEntry($patient, 'feeling', true);

        return back()->with('status', 'Que bom! Registrado que você está bem hoje.');
    }
}
