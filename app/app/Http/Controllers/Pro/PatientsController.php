<?php

namespace App\Http\Controllers\Pro;

use App\Domain\Catalog;
use App\Domain\Invites;
use App\Domain\ProData;
use App\Http\Controllers\Controller;
use App\Models\CareTeam;
use App\Models\Patient;
use App\Models\PatientGoal;
use App\Models\Professional;
use App\Models\User;
use App\Security\Audit;
use App\Security\Modules;
use App\Support\Clock;
use App\Support\Fmt;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PatientsController extends Controller
{
    public const FILTERS = ['todos' => 'Todos', 'alerta' => 'Com alerta', 'idle' => 'Sem registro', 'diario' => 'Desabafo novo', 'convidados' => 'Convidados'];

    public function index(Request $request)
    {
        $data = new ProData($this->user());
        $patients = $data->patients();
        $alerts = $data->openAlerts($patients);
        $unread = $data->unreadJournals($patients);
        $all = $patients->map(fn ($p) => $data->row($p, $alerts, $unread));

        $q = $this->norm((string) $request->query('q', ''));
        $filter = array_key_exists($request->query('filtro'), self::FILTERS) ? $request->query('filtro') : 'todos';
        $test = fn ($r, $f) => match ($f) {
            'alerta' => $r['alerts'] > 0, 'idle' => $r['idle'], 'diario' => $r['unread'] > 0, 'convidados' => $r['p']->status === 'invited', default => true,
        };
        $list = $all->filter(fn ($r) => $q === '' || str_contains($this->norm((string) $r['p']->name), $q))
            ->filter(fn ($r) => $test($r, $filter))
            ->sortBy([fn ($a, $b) => $b['alerts'] <=> $a['alerts'], fn ($a, $b) => $b['unread'] <=> $a['unread']])->values();

        return view('pro.patients', [
            'list' => $list, 'q' => $request->query('q', ''), 'filter' => $filter,
            'counts' => collect(self::FILTERS)->map(fn ($l, $f) => $all->filter(fn ($r) => $test($r, $f))->count()),
            'active' => $all->where('p.status', 'active')->count(),
        ]);
    }

    public function create()
    {
        return view('pro.patient-create', [
            'team' => Professional::where('role', 'member')->whereHas('user', fn ($q) => $q->where('status', 'active'))->orderBy('name')->get(),
            'perKg' => (int) Settings::get('water_ml_per_kg'),
            'protKg' => Settings::get('protein_g_per_kg'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9()+\-\s]*$/'],
            'birth_date' => ['nullable', 'string', 'max:10'],
            'objective' => ['nullable', Rule::in(Catalog::OBJECTIVES)],
            'weight' => ['nullable', 'string', 'max:8'],
            'water' => ['nullable', 'integer', 'between:1000,6000'],
            'protein' => ['nullable', 'integer', 'between:20,400'],
            'team' => ['nullable', 'array'],
            'team.*' => ['string'],
            'is_minor' => ['nullable', 'boolean'],
            'guardian_name' => ['nullable', 'required_if:is_minor,1', 'string', 'max:160'],
        ], [
            'name.*' => 'Digite o nome completo.', 'email.*' => 'Digite um e-mail válido.',
            'guardian_name.required_if' => 'Para menores de idade, informe o responsável legal.',
        ]);

        if (User::findByEmail($data['email'])) {
            return back()->withErrors(['email' => 'Já existe uma conta com este e-mail.'])->withInput();
        }
        $birth = null;
        if (! empty($data['birth_date'])) {
            $d = Clock::parseDate($data['birth_date']);
            if (! $d || $d->isFuture()) {
                return back()->withErrors(['birth_date' => 'Use a data no formato dd/mm/aaaa.'])->withInput();
            }
            $birth = $d->toDateString();
        }
        $weight = Fmt::parseNumber($data['weight'] ?? null);
        if ($weight !== null && ($weight < 30 || $weight > 250)) {
            return back()->withErrors(['weight' => 'Confira o peso meta (entre 30 e 250 kg).'])->withInput();
        }

        $members = Professional::where('role', 'member')->whereIn('user_id', array_keys($data['team'] ?? []))->get();

        [$patient, $code] = DB::transaction(function () use ($data, $birth, $weight, $members) {
            $user = new User(['type' => 'patient', 'status' => 'pending']);
            $user->setEmailAddress($data['email']);
            $user->save();

            $patient = new Patient(['user_id' => $user->id, 'started_on' => Clock::todayIso(), 'status' => 'invited', 'created_by' => $this->user()->id,
                'is_minor' => (bool) ($data['is_minor'] ?? false), 'guardian_name' => $data['guardian_name'] ?? null]);
            $patient->name = trim($data['name']);
            $patient->phone = $data['phone'] ?? null;
            $patient->birth_date = $birth;
            $patient->objective = $data['objective'] ?? null;
            $patient->save();

            foreach (array_filter(['weight' => $weight, 'water' => $data['water'] ?? 2500, 'protein' => $data['protein'] ?? 100], fn ($v) => $v !== null) as $type => $target) {
                $g = new PatientGoal(['patient_id' => $patient->id, 'type' => $type, 'set_by' => $this->user()->id]);
                $g->target = $target;
                $g->save();
            }

            foreach ($members as $m) {
                CareTeam::create(['patient_id' => $patient->id, 'professional_id' => $m->user_id, 'modules' => RecordController::defaultModules($m->specialty)]);
            }
            Audit::log('patient.create', 'patient:'.$patient->id, $patient->id);

            return [$patient, Invites::forPatient($patient, $user)];
        });

        Invites::mail($patient->user, $code, Settings::leadName());

        return redirect()->route('pro.patient', [$patient, 'aba' => 'resumo'])->with('invite_code', $code)->with('status', 'Paciente cadastrado. O convite foi enviado por e-mail.');
    }

    private function norm(string $s): string
    {
        return mb_strtolower(\Illuminate\Support\Str::ascii(trim($s)));
    }
}
