<?php

namespace App\Http\Controllers\Pro;

use App\Domain\AlertEngine;
use App\Domain\Invites;
use App\Domain\Notice;
use App\Http\Controllers\Controller;
use App\Models\Professional;
use App\Models\User;
use App\Security\Audit;
use App\Security\Modules;
use App\Support\Fmt;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Configurações do ambiente. Só o master muda; a equipe vê o resumo e a própria conta. */
class SettingsController extends Controller
{
    public const CLINIC_FIELDS = [
        'clinic_name' => ['Nome do consultório', 'house-medical', 120],
        'clinic_city' => ['Cidade', 'location-dot', 80],
        'clinic_contact' => ['Contato do consultório', 'phone', 120],
        'clinic_hours' => ['Horário de atendimento', 'clock', 80],
        'controller_name' => ['Controlador dos dados (nome ou razão social)', 'building', 160],
        'controller_document' => ['CPF ou CNPJ do controlador', 'id-card', 20],
        'dpo_name' => ['Encarregado (DPO)', 'user-shield', 120],
        'dpo_contact' => ['E-mail do encarregado', 'envelope', 120],
    ];

    public function index()
    {
        $user = $this->user();
        $view = ['me' => $user->professional, 'master' => $user->isMaster(), 'team' => Professional::with('user')->orderByRaw("role = 'master' desc")->orderBy('name')->get()];
        if ($user->isMaster()) {
            $view += [
                'modules' => Modules::map(),
                'goals' => collect(Modules::GOALS)->mapWithKeys(fn ($l, $k) => [$k => Modules::goalEnabled($k)])->all(),
                'rules' => collect(AlertEngine::RULES)->mapWithKeys(fn ($r, $k) => [$k => AlertEngine::enabled($k)])->all(),
                'idleOn' => AlertEngine::enabled('idle'), 'idleDays' => AlertEngine::idleDays(),
                'settings' => collect(Settings::DEFAULTS)->keys()->mapWithKeys(fn ($k) => [$k => Settings::get($k)])->all(),
                'notice' => Notice::current(), 'draft' => Notice::draft(),
            ];
        }

        return view('pro.settings', $view);
    }

    public function modules(Request $request)
    {
        $request->validate(['module' => ['required', Rule::in(array_keys(Modules::ALL))], 'value' => ['required', 'boolean']]);
        $user = $this->user();
        Modules::set($request->input('module'), $request->boolean('value'), $user->auditName(), $user->id);

        return back()->with('status', Modules::ALL[$request->input('module')].($request->boolean('value') ? ' ligado' : ' desligado').' para todos os pacientes.');
    }

    public function goalToggles(Request $request)
    {
        $request->validate(['goal' => ['required', Rule::in(array_keys(Modules::GOALS))], 'value' => ['required', 'boolean']]);
        Modules::setGoal($request->input('goal'), $request->boolean('value'));

        return back()->with('status', 'Meta de '.mb_strtolower(Modules::GOALS[$request->input('goal')]).($request->boolean('value') ? ' ligada.' : ' desligada.'));
    }

    public function rules(Request $request)
    {
        $request->validate(['rule' => ['required', Rule::in([...array_keys(AlertEngine::RULES), 'idle'])], 'value' => ['nullable', 'boolean'], 'days' => ['nullable', Rule::in(AlertEngine::IDLE_OPTIONS)]]);
        $rule = $request->input('rule');
        $row = ['updated_at' => now(), 'created_at' => now()];
        if ($request->has('value')) {
            $row['enabled'] = $request->boolean('value');
        }
        if ($rule === 'idle' && $request->filled('days')) {
            $row['params'] = json_encode(['days' => (int) $request->input('days')]);
        }
        DB::table('alert_rules')->updateOrInsert(['key' => $rule], $row);
        AlertEngine::flush();
        Audit::log('alert.rule', 'rule:'.$rule, null, array_intersect_key($row, ['enabled' => 1, 'params' => 1]));

        return back()->with('status', 'Regra de alerta atualizada.');
    }

    public function defaults(Request $request)
    {
        $request->validate(['water_ml_per_kg' => ['required', 'integer', 'between:20,60'], 'protein_g_per_kg' => ['required', 'string', 'max:5'], 'bio_interval_days' => ['required', 'integer', 'between:7,120']]);
        $prot = Fmt::parseNumber($request->input('protein_g_per_kg'));
        if ($prot === null || $prot < 0.5 || $prot > 3.5) {
            return back()->withErrors(['protein_g_per_kg' => 'Use um valor entre 0,5 e 3,5 g/kg.'])->withInput();
        }
        Settings::set(['water_ml_per_kg' => $request->integer('water_ml_per_kg'), 'protein_g_per_kg' => Fmt::num($prot), 'bio_interval_days' => $request->integer('bio_interval_days')]);

        return back()->with('status', 'Metas padrão salvas. Valem para os próximos pacientes.');
    }

    public function clinic(Request $request)
    {
        $rules = [];
        foreach (self::CLINIC_FIELDS as $k => [, , $max]) {
            $rules[$k] = [$k === 'clinic_name' ? 'required' : 'nullable', 'string', 'max:'.$max];
        }
        $rules['dpo_contact'][] = 'email:rfc';
        $data = $request->validate($rules, ['clinic_name.required' => 'Digite o nome do consultório.', 'dpo_contact.email' => 'Digite um e-mail válido.']);
        Settings::set(array_map(fn ($v) => trim((string) $v), $data));

        return back()->with('status', 'Dados do consultório salvos. Se mudou algo do aviso de privacidade, publique uma nova versão.');
    }

    public function inviteMember(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'specialty' => ['required', Rule::in(array_keys(Professional::SPECIALTIES))],
            'council_number' => ['nullable', 'string', 'max:30'],
        ], ['name.*' => 'Digite o nome completo.', 'email.*' => 'Digite um e-mail válido.', 'specialty.*' => 'Escolha a especialidade.']);

        $existing = User::findByEmail($data['email']);
        if ($existing && ! ($existing->isProfessional() && $existing->status === 'pending')) {
            return back()->withErrors(['email' => 'Já existe uma conta com este e-mail.'])->withInput();
        }

        $code = DB::transaction(function () use ($data, $existing) {
            $user = $existing ?? new User(['type' => 'professional', 'status' => 'pending']);
            if (! $existing) {
                $user->setEmailAddress($data['email']);
                $user->save();
            }
            Professional::updateOrCreate(['user_id' => $user->id], [
                'name' => trim($data['name']), 'role' => 'member', 'specialty' => $data['specialty'],
                'council' => Professional::SPECIALTIES[$data['specialty']]['council'], 'council_number' => $data['council_number'] ?? null,
            ]);

            return Invites::forProfessional($user);
        });
        $user = User::findByEmail($data['email']);
        Invites::mail($user, $code, $this->user()->professional->name);

        return back()->with('member_link', route('setup', str_replace('-', '', $code)))->with('status', 'Convite enviado para '.trim($data['name']).'.');
    }

    public function memberStatus(Request $request, User $user)
    {
        abort_unless($user->isProfessional() && $user->professional?->role === 'member', 404);
        $request->validate(['action' => ['required', Rule::in(['block', 'unblock', 'reinvite'])]]);
        $action = $request->input('action');

        if ($action === 'reinvite') {
            abort_unless($user->status === 'pending', 422);
            $code = Invites::forProfessional($user);
            Invites::mail($user, $code, $this->user()->professional->name);

            return back()->with('member_link', route('setup', str_replace('-', '', $code)))->with('status', 'Novo convite enviado. O anterior deixou de valer.');
        }

        $user->forceFill(['status' => $action === 'block' ? 'blocked' : 'active'])->save();
        if ($action === 'block') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        Audit::log('team.'.$action, 'user:'.$user->id);

        return back()->with('status', $user->professional->name.($action === 'block' ? ' perdeu o acesso ao painel.' : ' voltou a ter acesso.'));
    }

    public function publishNotice(Request $request)
    {
        $request->validate(['text' => ['required', 'string', 'min:200', 'max:60000'], 'relevant' => ['nullable', 'boolean']], ['text.*' => 'O aviso precisa ter o texto completo.']);
        $v = Notice::publish(trim($request->input('text')), $request->boolean('relevant'), $this->user()->id);

        return back()->with('status', 'Aviso de privacidade versão '.$v.' publicado.'.($request->boolean('relevant') ? ' Os pacientes vão rever no próximo acesso.' : ''));
    }
}
