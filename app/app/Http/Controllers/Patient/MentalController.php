<?php

namespace App\Http\Controllers\Patient;

use App\Domain\AlertEngine;
use App\Domain\Catalog;
use App\Domain\PatientData;
use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\TherapySession;
use App\Security\Audit;
use App\Security\Consents;
use App\Support\Clock;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Saúde mental: sessões de terapia, calendário de humor e desabafos (privados por padrão). */
class MentalController extends Controller
{
    public function index()
    {
        $patient = $this->patient();
        $data = new PatientData($patient);
        $sessions = TherapySession::of($patient)->orderBy('starts_at')->get();
        $startToday = Clock::today()->startOfDay()->utc();

        return view('patient.mental', [
            'next' => $sessions->first(fn ($s) => $s->status === 'agendada' && $s->starts_at->gte($startToday)),
            'past' => $sessions->filter(fn ($s) => $s->starts_at->lt($startToday) || $s->status !== 'agendada')->reverse()->values(),
            'journal' => $data->journal(false),
            'who' => $data->teamBy('Psicologia') ?? Settings::leadName(),
            'lead' => Settings::leadName(),
        ]);
    }

    public function toggleTherapy(TherapySession $session)
    {
        $this->own($session);
        abort_if($session->starts_at->isFuture(), 422, 'A sessão ainda não aconteceu.');
        $session->update(['status' => $session->status === 'feita' ? 'faltou' : 'feita']);

        return back();
    }

    public function create()
    {
        return view('patient.journal-create', ['shareDefault' => Consents::has($this->patient(), 'journal_share'), 'lead' => Settings::leadName()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mood' => ['required', Rule::in(array_keys(Catalog::MOODS))],
            'tags' => ['nullable', 'array'],
            'tags.*' => [Rule::in(Catalog::JOURNAL_TAGS)],
            'text' => ['required', 'string', 'max:5000'],
            'shared' => ['nullable', 'boolean'],
        ], ['mood.required' => 'Escolha como você está.', 'text.required' => 'Escreva algo para salvar.']);

        $shared = (bool) ($data['shared'] ?? false);
        $j = new JournalEntry(['patient_id' => $this->patient()->id, 'day' => Clock::todayIso(), 'shared' => $shared, 'shared_at' => $shared ? now() : null]);
        $j->mood = (int) $data['mood'];
        $j->tags = array_values($data['tags'] ?? []);
        $j->text = trim($data['text']);
        $j->save();
        AlertEngine::onJournal($j);

        return redirect()->route('mental')->with('status', 'Desabafo salvo.');
    }

    public function show(JournalEntry $entry)
    {
        $this->own($entry);

        return view('patient.journal-show', ['j' => $entry, 'lead' => Settings::leadName()]);
    }

    public function share(Request $request, JournalEntry $entry)
    {
        $this->own($entry);
        $on = $request->boolean('shared');
        $entry->forceFill(['shared' => $on, 'shared_at' => $on ? ($entry->shared_at ?? now()) : $entry->shared_at])->save();
        if ($on) {
            AlertEngine::onJournal($entry);
        } else {
            AlertEngine::withdraw('journal', $entry->id);
        }

        return back()->with('status', $on ? Settings::leadName().' agora pode ler.' : 'Desabafo agora é privado.');
    }

    public function destroy(JournalEntry $entry)
    {
        $this->own($entry);
        AlertEngine::withdraw('journal', $entry->id);
        // Nunca compartilhado: some de vez. Já lido pela equipe: sai das telas, mas fica na guarda do prontuário.
        $entry->shared_at ? $entry->delete() : $entry->forceDelete();
        Audit::log('journal.delete', 'journal:'.$entry->id, $entry->patient_id);

        return redirect()->route('mental')->with('status', 'Desabafo apagado.');
    }
}
