<?php

namespace App\Http\Controllers\Pro;

use App\Domain\ProData;
use App\Http\Controllers\Controller;
use App\Models\ClinicalNote;
use App\Models\JournalEntry;
use App\Security\Access;
use App\Security\Audit;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Desabafos que os pacientes escolheram compartilhar. Os privados nunca chegam aqui. */
class JournalsController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->user();
        $data = new ProData($user);
        $patients = $data->patients()->filter(fn ($p) => $data->canSee($p, 'saude_mental'));
        $byId = $patients->keyBy('id');
        $filter = $request->query('filtro') === 'todos' ? 'todos' : 'nao';

        $all = JournalEntry::whereIn('patient_id', $byId->keys())->where('shared', true)->orderByDesc('day')->orderByDesc('created_at')->limit(200)->get();
        $read = DB::table('journal_reads')->where('professional_id', $user->id)->whereIn('journal_id', $all->pluck('id'))->pluck('journal_id')->flip();

        $cur = $request->query('abrir') ? $all->firstWhere('id', $request->query('abrir')) : null;
        $wasUnread = $cur && ! isset($read[$cur->id]);
        if ($cur && $wasUnread) {
            DB::table('journal_reads')->insertOrIgnore(['journal_id' => $cur->id, 'professional_id' => $user->id, 'read_at' => now()]);
            Audit::log('journal.read', 'journal:'.$cur->id, $cur->patient_id);
        }

        $list = $all->filter(fn ($j) => $filter === 'todos' || ! isset($read[$j->id]) || $j->id === $cur?->id)->values();

        return view('pro.journals', [
            'list' => $list, 'cur' => $cur, 'filter' => $filter, 'read' => $read, 'names' => $byId->map(fn ($p) => $p->name),
            'unreadCount' => $all->filter(fn ($j) => ! isset($read[$j->id]))->count() - ($wasUnread ? 1 : 0),
            'canNote' => $cur ? Access::canWrite($user, $byId[$cur->patient_id], 'notas') : false,
        ]);
    }

    public function toNotes(JournalEntry $entry)
    {
        abort_unless($entry->shared, 404);
        $patient = $entry->patient;
        Access::authorizeView($this->user(), $patient, 'saude_mental');
        Access::authorizeWrite($this->user(), $patient, 'notas');

        $n = new ClinicalNote(['patient_id' => $patient->id, 'author_id' => $this->user()->id]);
        $n->text = 'Desabafo de '.Fmt::dm($entry->day).': abordar na consulta.';
        $n->save();
        Audit::log('journal.to_note', 'journal:'.$entry->id, $patient->id);

        return back()->with('status', 'Adicionado às anotações de '.$patient->firstName().'.');
    }
}
