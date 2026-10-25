<?php

namespace App\Http\Controllers\Pro;

use App\Domain\Notice;
use App\Http\Controllers\Controller;
use App\Security\Modules;
use App\Support\Settings;
use Illuminate\Http\Request;

/** Assistente que o master vê no primeiro acesso: consultório, LGPD, módulos e aviso de privacidade. */
class SetupWizardController extends Controller
{
    public const STEPS = ['consultorio' => 'Consultório', 'lgpd' => 'Controlador e encarregado', 'modulos' => 'Funções do app', 'aviso' => 'Aviso de privacidade'];

    public function show(Request $request)
    {
        $step = array_key_exists($request->query('passo'), self::STEPS) ? $request->query('passo') : 'consultorio';

        return view('pro.setup', [
            'step' => $step, 'steps' => self::STEPS,
            'settings' => collect(Settings::DEFAULTS)->keys()->mapWithKeys(fn ($k) => [$k => Settings::get($k)])->all(),
            'fields' => SettingsController::CLINIC_FIELDS, 'modules' => Modules::map(), 'draft' => Notice::draft(), 'notice' => Notice::current(),
        ]);
    }

    public function store(Request $request)
    {
        $step = $request->input('step');
        $keys = array_keys(self::STEPS);
        $next = $keys[array_search($step, $keys, true) + 1] ?? null;

        if ($step === 'consultorio' || $step === 'lgpd') {
            $fields = $step === 'consultorio' ? ['clinic_name', 'clinic_city', 'clinic_contact', 'clinic_hours'] : ['controller_name', 'controller_document', 'dpo_name', 'dpo_contact'];
            $rules = [];
            foreach ($fields as $f) {
                $rules[$f] = [in_array($f, ['clinic_name', 'controller_name', 'dpo_name', 'dpo_contact'], true) ? 'required' : 'nullable', 'string', 'max:'.SettingsController::CLINIC_FIELDS[$f][2]];
            }
            if ($step === 'lgpd') {
                $rules['dpo_contact'][] = 'email:rfc';
            }
            $data = $request->validate($rules, ['*.required' => 'Preencha este campo.', 'dpo_contact.email' => 'Digite um e-mail válido.']);
            Settings::set(array_map(fn ($v) => trim((string) $v), $data));
        } elseif ($step === 'modulos') {
            $on = (array) $request->input('modules', []);
            $user = $this->user();
            foreach (array_keys(Modules::ALL) as $m) {
                if (Modules::enabled($m) !== in_array($m, $on, true)) {
                    Modules::set($m, in_array($m, $on, true), $user->auditName(), $user->id);
                }
            }
        } elseif ($step === 'aviso') {
            $request->validate(['text' => ['required', 'string', 'min:200', 'max:60000']], ['text.*' => 'O aviso precisa ter o texto completo.']);
            Notice::publish(trim($request->input('text')), true, $this->user()->id);
            Settings::set(['setup_completed' => '1']);

            return redirect()->route('pro.dashboard')->with('status', 'Tudo pronto. Agora é só convidar os pacientes.');
        } else {
            abort(422);
        }

        return redirect()->route('pro.setup', ['passo' => $next]);
    }
}
