<?php

namespace App\Http\Controllers\Pro;

use App\Domain\AlertEngine;
use App\Domain\Catalog;
use App\Domain\Indicators;
use App\Domain\Invites;
use App\Domain\PatientData;
use App\Domain\ProData;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Patient\CareController;
use App\Http\Controllers\Patient\EvolutionController;
use App\Http\Controllers\Patient\PhotosController;
use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\CareTeam;
use App\Models\ClinicalNote;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\PatientGoal;
use App\Models\Professional;
use App\Models\StoredFile;
use App\Models\TherapySession;
use App\Security\Access;
use App\Security\Audit;
use App\Security\Consents;
use App\Security\Modules;
use App\Support\Clock;
use App\Support\Fmt;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Prontuário do paciente no painel. Cada aba confere se o profissional tem o módulo liberado. */
class RecordController extends Controller
{
    /** aba => [rótulo, ícone, módulo exigido (null = sempre)] */
    public const TABS = [
        'resumo' => ['Resumo', 'gauge', null],
        'bio' => ['Bioimpedância', 'chart-line', 'bioimpedancia'],
        'fotos' => ['Fotos', 'images', 'fotos'],
        'exames' => ['Exames', 'file-medical', 'exames'],
        'habitos' => ['Hábitos', 'list-check', 'metas'],
        'sintomas' => ['Sintomas', 'notes-medical', 'sintomas'],
        'desabafos' => ['Desabafos', 'book-open', 'saude_mental'],
        'multi' => ['Multidisciplinar', 'people-group', ['nutricao', 'atividade', 'saude_mental']],
        'notas' => ['Anotações', 'pen-clip', null],
        'gestao' => ['Cadastro e acesso', 'user-gear', 'master'],
    ];

    /** Módulos que cada especialidade recebe por padrão ao entrar na equipe de um paciente. */
    public static function defaultModules(?string $specialty): array
    {
        return match ($specialty) {
            'Psicologia' => ['saude_mental'],
            'Educação física' => ['atividade', 'bioimpedancia', 'metas'],
            'Nutrição' => ['nutricao', 'metas', 'bioimpedancia', 'sintomas', 'exames'],
            default => ['metas', 'sintomas', 'bioimpedancia', 'exames', 'nutricao'],
        };
    }

    public function show(Request $request, Patient $patient)
    {
        $user = $this->user();
        Access::authorizeView($user, $patient);
        $tabs = collect(self::TABS)->filter(fn ($t) => $this->tabAllowed($patient, $t[2]))->all();
        $tab = array_key_exists((string) $request->query('aba'), $tabs) ? $request->query('aba') : 'resumo';

        $pro = new ProData($user);
        $data = new PatientData($patient);
        $alerts = $pro->openAlerts(collect([$patient]));
        $unread = $pro->unreadJournals(collect([$patient]));
        $view = [
            'patient' => $patient, 'tab' => $tab, 'tabs' => $tabs, 'row' => $pro->row($patient, $alerts, $unread),
            'next' => Appointment::of($patient)->where('status', 'agendada')->where('starts_at', '>=', now())->orderBy('starts_at')->first(),
            'canWrite' => fn ($m) => Access::canWrite($user, $patient, $m),
            'm' => Modules::map($patient->id),
        ];

        $view += match ($tab) {
            'resumo' => $this->summary($patient, $data, $pro),
            'bio' => ['charts' => EvolutionController::charts($data, $request), 'files' => StoredFile::of($patient)->where('kind', 'laudo')->get()->keyBy('id')],
            'fotos' => $this->photos($patient, $data, $request),
            'exames' => ['exams' => StoredFile::of($patient)->whereIn('kind', ['exame', 'laudo'])->orderByDesc('taken_on')->get()],
            'habitos' => ['grid' => $data->habitGrid(true), 'rates' => collect(['water', 'protein', 'fvl'])->filter(fn ($g) => Modules::goalEnabled($g))->mapWithKeys(fn ($g) => [$g => $data->goalRate($g)])->all(), 'today' => $data->day(Clock::today()), 'goals' => $data->goals()],
            'sintomas' => $this->symptoms($patient, $data),
            'desabafos' => $this->journals($patient, $data),
            'multi' => $this->multi($patient, $data),
            'notas' => ['notes' => ClinicalNote::of($patient)->orderByDesc('created_at')->get(), 'consults' => Consultation::of($patient)->orderByDesc('day')->get(), 'names' => Professional::pluck('name', 'user_id')],
            'gestao' => $this->management($patient),
        };

        Audit::log('record.view', 'patient:'.$patient->id, $patient->id, ['tab' => $tab]);

        return view('pro.record', $view);
    }

    private function tabAllowed(Patient $patient, $req): bool
    {
        $user = $this->user();
        if ($req === null) {
            return true;
        }
        if ($req === 'master') {
            return $user->isMaster();
        }
        foreach ((array) $req as $m) {
            if (Access::canView($user, $patient, $m)) {
                return true;
            }
        }

        return false;
    }

    private function summary(Patient $patient, PatientData $data, ProData $pro): array
    {
        $user = $this->user();
        $bio = Access::canView($user, $patient, 'bioimpedancia') ? $data->bio() : collect();
        $symptoms = Access::canView($user, $patient, 'sintomas') ? $data->symptoms(30) : collect();
        $journal = Access::canView($user, $patient, 'saude_mental') ? $data->journal(true) : collect();

        $events = collect();
        foreach ($bio->slice(-3) as $b) {
            $events->push(['d' => $b['d'], 'icon' => 'weight-scale', 't' => 'Registrou bioimpedância: '.Fmt::num($b['w']).' kg, '.Fmt::num($b['f']).'% de gordura', 'tone' => null]);
        }
        foreach ($symptoms->take(6) as $s) {
            $events->push(['d' => $s->day, 'icon' => 'notes-medical', 't' => 'Sintoma: '.$s->symptom.' ('.mb_strtolower($s->intensity).')', 'tone' => Catalog::INT_TONE[$s->intensity]]);
        }
        foreach ($journal->take(4) as $j) {
            $events->push(['d' => $j->day, 'icon' => Catalog::mood((int) $j->mood)['icon'], 'r' => true, 't' => 'Escreveu um desabafo · '.mb_strtolower(Catalog::mood((int) $j->mood)['l']), 'tone' => null]);
        }
        if (Access::canView($user, $patient, 'atividade')) {
            foreach ($data->workouts()->take(4) as $w) {
                $events->push(['d' => $w->day, 'icon' => $w->category === 'Força' ? 'dumbbell' : 'heart-pulse', 't' => 'Treino '.($w->category === 'Força' ? 'de força' : 'aeróbico').': '.$w->modality.', '.$w->minutes.' min', 'tone' => null]);
            }
        }

        return [
            'bio' => $bio, 'lastSymptom' => $symptoms->first(), 'lastJournal' => $journal->first(),
            'events' => $events->sortByDesc(fn ($e) => $e['d']->timestamp)->take(9)->values(),
            'goals' => $data->goals(),
        ];
    }

    private function photos(Patient $patient, PatientData $data, Request $request): array
    {
        if (! Consents::has($patient, 'photos')) {
            return ['consent' => false];
        }
        $sessions = EvolutionController::photoSessions($patient->id);
        $out = ['consent' => true, 'sessions' => $sessions];
        if ($sessions->count() >= 2) {
            $out['compare'] = PhotosController::compareData($sessions, $data->bio(), $request);
        }

        return $out;
    }

    private function symptoms(Patient $patient, PatientData $data): array
    {
        $since = Clock::today()->subDays(29);
        $list = $data->symptomsBetween($since, Clock::today())->sortByDesc(fn ($s) => $s->day->timestamp)->values();
        $freq = $list->groupBy('symptom')->map(fn ($g, $name) => ['l' => $name, 'v' => $g->count(), 'tone' => $g->contains('intensity', 'Forte') ? 'bad' : ($g->contains('intensity', 'Moderada') ? 'warn' : 'ok')])->sortByDesc('v')->values();

        return ['symptoms' => $list, 'freq' => $freq, 'alertsBySource' => \App\Models\Alert::of($patient)->where('source_type', 'symptom')->get()->keyBy('source_id')];
    }

    private function journals(Patient $patient, PatientData $data): array
    {
        $list = $data->journal(true);
        $now = now();
        foreach ($list as $j) {
            DB::table('journal_reads')->insertOrIgnore(['journal_id' => $j->id, 'professional_id' => $this->user()->id, 'read_at' => $now]);
        }
        if ($list->isNotEmpty()) {
            Audit::log('journal.read', 'patient:'.$patient->id, $patient->id, ['count' => $list->count()]);
        }

        return ['journal' => $list];
    }

    private function multi(Patient $patient, PatientData $data): array
    {
        $user = $this->user();
        $out = ['who' => ['nutricao' => $data->teamBy('Nutrição') ?? Settings::leadName(), 'atividade' => $data->teamBy('Educação física'), 'saude_mental' => $data->teamBy('Psicologia')]];
        if (Access::canView($user, $patient, 'nutricao')) {
            $out['nutrition'] = CareController::nutritionData($patient);
        }
        if (Access::canView($user, $patient, 'atividade')) {
            $out['workouts'] = $data->workouts()->take(20);
        }
        if (Access::canView($user, $patient, 'saude_mental')) {
            $out['therapy'] = TherapySession::of($patient)->orderByDesc('starts_at')->limit(12)->get();
        }

        return $out;
    }

    private function management(Patient $patient): array
    {
        return [
            'overrides' => Modules::overrides($patient->id),
            'defaults' => Modules::map(),
            'members' => Professional::where('role', 'member')->whereHas('user', fn ($q) => $q->where('status', 'active'))->orderBy('name')->get(),
            'team' => CareTeam::where('patient_id', $patient->id)->get()->keyBy('professional_id'),
            'pendingInvite' => $patient->user && $patient->user->status === 'pending',
        ];
    }

    /** Preparação da consulta: tudo desde a última consulta registrada. */
    public function prep(Patient $patient)
    {
        $user = $this->user();
        Access::authorizeView($user, $patient);
        $data = new PatientData($patient);
        $pro = new ProData($user);
        $lastConsult = Consultation::of($patient)->orderByDesc('day')->first();
        $since = $lastConsult?->day ?? $patient->started_on;

        $bio = Access::canView($user, $patient, 'bioimpedancia') ? $data->bio() : collect();
        $cur = $bio->last();
        $base = $bio->filter(fn ($b) => $b['d']->lte($since))->last() ?? ($bio->count() > 1 ? $bio[$bio->count() - 2] : null);
        $symptoms = Access::canView($user, $patient, 'sintomas') ? $data->symptomsBetween($since, Clock::today()) : collect();
        $journal = Access::canView($user, $patient, 'saude_mental') ? $data->journal(true)->filter(fn ($j) => $j->day->gte($since)) : collect();
        $row = $pro->row($patient, $pro->openAlerts(collect([$patient])), collect());
        $missed = Access::canView($user, $patient, 'saude_mental') ? TherapySession::of($patient)->where('status', 'faltou')->where('starts_at', '>=', $since)->get() : collect();

        $topics = array_values(array_filter([
            $symptoms->isNotEmpty() ? 'Conversar sobre '.$symptoms->pluck('symptom')->unique()->map(fn ($s) => mb_strtolower($s))->join(', ', ' e ').' ('.$symptoms->count().' '.Fmt::plural($symptoms->count(), 'registro', 'registros').')' : null,
            $journal->contains(fn ($j) => (int) $j->mood <= 2) ? 'Desabafo recente com humor baixo: acolher e entender o contexto' : null,
            $cur && $base ? (Indicators::toneOfDelta($cur['w'] - $base['w'], 'down') !== 'ok' ? 'Peso não caiu desde a última consulta: revisar o plano' : 'Reforçar progresso: '.Fmt::num(abs($cur['w'] - $base['w'])).' kg a menos desde a última consulta') : null,
            $row['adh'] !== null && $row['adh'] < 75 ? 'Adesão às metas em '.$row['adh'].'%: identificar barreiras' : null,
            $missed->isNotEmpty() ? 'Faltou a '.$missed->count().' '.Fmt::plural($missed->count(), 'sessão', 'sessões').' de terapia' : null,
            $row['idle'] ? 'Paciente sem registrar no app recentemente' : null,
        ]));
        Audit::log('record.prep', 'patient:'.$patient->id, $patient->id);

        return view('pro.prep', [
            'patient' => $patient, 'since' => $since, 'cur' => $cur, 'base' => $base, 'symptoms' => $symptoms, 'journal' => $journal, 'topics' => $topics,
            'next' => Appointment::of($patient)->where('status', 'agendada')->where('starts_at', '>=', now())->orderBy('starts_at')->first(),
            'alertsBySource' => \App\Models\Alert::of($patient)->where('source_type', 'symptom')->get()->keyBy('source_id'),
        ]);
    }

    public function note(Request $request, Patient $patient)
    {
        Access::authorizeWrite($this->user(), $patient, 'notas');
        $request->validate(['text' => ['required', 'string', 'max:5000']], ['text.required' => 'Escreva a anotação.']);
        $n = new ClinicalNote(['patient_id' => $patient->id, 'author_id' => $this->user()->id]);
        $n->text = trim($request->input('text'));
        $n->save();
        Audit::log('record.note', 'note:'.$n->id, $patient->id);

        return redirect()->route('pro.patient', [$patient, 'aba' => 'notas'])->with('status', 'Anotação salva.');
    }

    public function goals(Request $request, Patient $patient)
    {
        Access::authorizeWrite($this->user(), $patient, 'metas');
        $request->validate(['weight' => ['nullable', 'string', 'max:8'], 'water' => ['required', 'integer', 'between:1000,6000'], 'protein' => ['required', 'integer', 'between:20,400']]);
        $weight = Fmt::parseNumber($request->input('weight'));
        if ($weight !== null && ($weight < 30 || $weight > 250)) {
            return back()->withErrors(['weight' => 'Confira o peso meta.']);
        }
        foreach (['weight' => $weight, 'water' => $request->integer('water'), 'protein' => $request->integer('protein')] as $type => $value) {
            $g = PatientGoal::of($patient)->where('type', $type)->first();
            if ($value === null) {
                $g?->delete();

                continue;
            }
            $g ??= new PatientGoal(['patient_id' => $patient->id, 'type' => $type]);
            $g->target = $value;
            $g->set_by = $this->user()->id;
            $g->save();
        }
        Audit::log('record.goals', 'patient:'.$patient->id, $patient->id);

        return back()->with('status', 'Metas atualizadas.');
    }

    public function consultation(Request $request, Patient $patient)
    {
        Access::authorizeWrite($this->user(), $patient, 'notas');
        $data = $request->validate(['type' => ['required', Rule::in(Catalog::APPOINTMENT_TYPES)], 'day' => ['required', 'string', 'max:10'], 'note' => ['nullable', 'string', 'max:5000']]);
        $day = Clock::parseDate($data['day']);
        if (! $day || $day->isFuture()) {
            return back()->withErrors(['day' => 'Use uma data até hoje, no formato dd/mm/aaaa.'])->withInput();
        }
        $c = new Consultation(['patient_id' => $patient->id, 'professional_id' => $this->user()->id, 'day' => $day->toDateString(), 'type' => $data['type']]);
        $c->note = trim((string) ($data['note'] ?? '')) ?: null;
        $c->save();
        Appointment::of($patient)->where('status', 'agendada')->whereBetween('starts_at', [$day->startOfDay()->utc(), $day->endOfDay()->utc()])->update(['status' => 'atendida']);
        Audit::log('record.consultation', 'consultation:'.$c->id, $patient->id);

        return redirect()->route('pro.patient', [$patient, 'aba' => 'notas'])->with('status', 'Consulta registrada.');
    }

    public function plan(Request $request, Patient $patient)
    {
        Access::authorizeWrite($this->user(), $patient, 'nutricao');
        $request->validate(['arquivo' => ['required', 'file']], ['arquivo.required' => 'Escolha o PDF do plano.']);
        CareController::storePlan($patient, $request);
        Audit::log('record.plan', 'patient:'.$patient->id, $patient->id);
        if ($patient->user_id) {
            AppNotification::send($patient->user_id, 'file-lines', 'Seu plano alimentar foi atualizado.', route('nutrition'));
        }

        return back()->with('status', 'Plano alimentar enviado ao paciente.');
    }

    public function therapy(Request $request, Patient $patient)
    {
        Access::authorizeWrite($this->user(), $patient, 'saude_mental');
        $data = $request->validate(['day' => ['required', 'string', 'max:10'], 'time' => ['required', 'regex:/^\d{1,2}:\d{2}$/']], ['time.regex' => 'Use o horário no formato 19:00.']);
        $day = Clock::parseDate($data['day']);
        if (! $day) {
            return back()->withErrors(['day' => 'Use a data no formato dd/mm/aaaa.'])->withInput();
        }
        $at = CarbonImmutable::parse($day->toDateString().' '.$data['time'], config('bioma.timezone'))->utc();
        TherapySession::create(['patient_id' => $patient->id, 'starts_at' => $at, 'status' => 'agendada']);
        Audit::log('record.therapy', 'patient:'.$patient->id, $patient->id);

        return back()->with('status', 'Sessão agendada.');
    }

    public function remind(Patient $patient)
    {
        Access::authorizeView($this->user(), $patient);
        abort_unless($patient->user_id && $patient->isActive(), 422);
        AppNotification::send($patient->user_id, 'bell', 'Sua equipe sentiu sua falta. Que tal registrar como foi seu dia?', route('home'));
        Audit::log('record.remind', 'patient:'.$patient->id, $patient->id);

        return back()->with('status', 'Lembrete enviado para '.$patient->firstName().'.');
    }

    public function updateData(Request $request, Patient $patient)
    {
        abort_unless($this->user()->isMaster(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:160'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9()+\-\s]*$/'],
            'objective' => ['nullable', Rule::in(Catalog::OBJECTIVES)],
            'status' => ['required', Rule::in(['active', 'invited', 'closing', 'closed'])],
        ]);
        $patient->name = trim($data['name']);
        $patient->phone = $data['phone'] ?? null;
        $patient->objective = $data['objective'] ?? null;
        $patient->status = $data['status'];
        $patient->save();
        if (in_array($data['status'], ['closing', 'closed'], true) && $patient->user) {
            $patient->user->forceFill(['status' => 'closed'])->save();
            DB::table('sessions')->where('user_id', $patient->user_id)->delete();
        }
        Audit::log('record.data', 'patient:'.$patient->id, $patient->id, ['status' => $data['status']]);

        return back()->with('status', 'Cadastro atualizado.');
    }

    public function reinvite(Patient $patient)
    {
        Access::authorizeView($this->user(), $patient);
        abort_unless($patient->user && $patient->user->status === 'pending', 422, 'Este paciente já ativou a conta.');
        $code = Invites::forPatient($patient, $patient->user);
        Invites::mail($patient->user, $code, Settings::leadName());

        return back()->with('invite_code', $code)->with('status', 'Novo convite gerado. O anterior deixou de valer.');
    }

    public function modules(Request $request, Patient $patient)
    {
        $request->validate(['module' => ['required', Rule::in(array_keys(Modules::ALL))], 'state' => ['required', Rule::in(['on', 'off', 'default'])], 'reason' => ['nullable', 'string', 'max:200']]);
        $state = $request->input('state');
        $user = $this->user();
        Modules::setForPatient($patient->id, $request->input('module'), $state === 'default' ? null : $state === 'on', $user->auditName(), $user->id, $request->input('reason'));

        return back()->with('status', Modules::ALL[$request->input('module')].': '.($state === 'default' ? 'segue o padrão do consultório' : ($state === 'on' ? 'ligado' : 'desligado')).' para '.$patient->firstName().'.');
    }

    public function team(Request $request, Patient $patient)
    {
        $request->validate(['professional_id' => ['required', 'string'], 'modules' => ['nullable', 'array'], 'modules.*' => [Rule::in(array_keys(Modules::ALL))]]);
        $member = Professional::where('role', 'member')->where('user_id', $request->input('professional_id'))->firstOrFail();
        $modules = array_values($request->input('modules', []));

        if (! $modules) {
            CareTeam::where('patient_id', $patient->id)->where('professional_id', $member->user_id)->delete();
        } else {
            $row = CareTeam::where('patient_id', $patient->id)->where('professional_id', $member->user_id)->first();
            $row ? $row->update(['modules' => $modules]) : CareTeam::create(['patient_id' => $patient->id, 'professional_id' => $member->user_id, 'modules' => $modules]);
        }
        Access::flush();
        Audit::log('record.team', 'patient:'.$patient->id, $patient->id, ['professional' => $member->user_id, 'modules' => $modules]);

        return back()->with('status', $modules ? $member->name.' agora vê: '.collect($modules)->map(fn ($m) => Modules::ALL[$m])->join(', ').'.' : $member->name.' saiu da equipe deste paciente.');
    }
}
