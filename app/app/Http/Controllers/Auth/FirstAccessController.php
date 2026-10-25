<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Invites;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Patient;
use App\Security\Access;
use App\Security\Audit;
use App\Security\Consents;
use App\Security\Modules;
use App\Security\Passwords;
use App\Support\Fmt;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Primeiro acesso do paciente em 3 etapas: código do convite, senha com ciência do aviso de
 * privacidade, conferência dos dados. A conta foi criada pelo profissional com status pending.
 */
class FirstAccessController extends Controller
{
    public function code()
    {
        return view('auth.first-code', ['lead' => Settings::leadName()]);
    }

    public function checkCode(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:20']], ['code.required' => 'Digite o código do convite.']);

        // O formulário só tem o código, então o limite de 5 tentativas erradas vale por IP.
        $key = 'invite|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, (int) config('bioma.invite.max_attempts'))) {
            return back()->withErrors(['code' => 'Muitas tentativas. Espere 15 minutos ou peça um novo código à sua equipe.']);
        }

        $invite = Invites::find($request->input('code'), 'patient');
        if (! $invite) {
            RateLimiter::hit($key, 15 * 60);
            Audit::record('anônimo', 'invite.fail', 'invite:desconhecido');

            return back()->withInput()->withErrors(['code' => 'Código inválido ou vencido. Confira com sua equipe.']);
        }

        RateLimiter::clear($key);
        $request->session()->put('first.invite', $invite->id);

        return redirect()->route('first.password');
    }

    public function password(Request $request)
    {
        $invite = $this->invite($request);
        if (! $invite) {
            return redirect()->route('first');
        }

        return view('auth.first-password', [
            'patient' => $invite->patient,
            'email' => $invite->patient->user->email,
            'lead' => Settings::leadName(),
            'noticeVersion' => Consents::currentNoticeVersion(),
        ]);
    }

    public function savePassword(Request $request)
    {
        $invite = $this->invite($request);
        if (! $invite) {
            return redirect()->route('first');
        }

        $request->validate([
            'password' => ['required', 'string', 'max:128', 'confirmed'],
            'notice' => ['accepted'],
        ], [
            'password.required' => 'Crie uma senha.',
            'password.confirmed' => 'As duas senhas não são iguais.',
            'notice.accepted' => 'Para continuar, confirme que leu o aviso de privacidade.',
        ]);

        $user = $invite->patient->user;
        if ($problem = Passwords::problem($request->input('password'), [$user->email, $invite->patient->name])) {
            return back()->withErrors(['password' => $problem]);
        }

        $user->forceFill(['password' => $request->input('password')])->save();
        $request->session()->put('first.password', true);

        return redirect()->route('first.review');
    }

    public function review(Request $request)
    {
        $invite = $this->invite($request);
        if (! $invite || ! $request->session()->get('first.password')) {
            return redirect()->route('first');
        }
        $patient = $invite->patient->loadMissing('goals');

        $rows = [];
        if ($patient->goal('weight')) {
            $rows[] = ['bullseye', 'Peso meta', Fmt::num((float) $patient->goal('weight')).' kg'];
        }
        if (Modules::enabled('metas', $patient->id)) {
            if (Modules::goalEnabled('water')) {
                $rows[] = ['glass-water', 'Meta de água', Fmt::int((int) $patient->goal('water', 2500)).' ml por dia'];
            }
            if (Modules::goalEnabled('protein')) {
                $rows[] = ['drumstick-bite', 'Meta de proteína', (int) $patient->goal('protein', 100).' g por dia'];
            }
        }
        foreach ($patient->careTeam()->with('professional')->get() as $t) {
            $rows[] = [$t->professional->icon(), $t->professional->specialty ?? 'Equipe', $t->professional->name];
        }

        return view('auth.first-review', ['patient' => $patient, 'rows' => $rows, 'lead' => Settings::leadName()]);
    }

    public function finish(Request $request)
    {
        $invite = $this->invite($request);
        if (! $invite || ! $request->session()->get('first.password')) {
            return redirect()->route('first');
        }
        $data = $request->validate(['height' => ['nullable', 'string', 'max:6']]);
        $patient = $invite->patient;
        $user = $patient->user;

        $height = Fmt::parseNumber($data['height'] ?? null);
        if ($height !== null && ($height < 0.5 || $height > 2.6)) {
            return back()->withErrors(['height' => 'Confira a altura. Use metros, por exemplo 1,66.'])->withInput();
        }

        DB::transaction(function () use ($invite, $patient, $user, $height) {
            if ($height !== null) {
                $patient->height = Fmt::num($height, 2);
            }
            $patient->status = 'active';
            $patient->save();
            $user->forceFill(['status' => 'active'])->save();
            $invite->forceFill(['used_at' => now()])->save();
            Consents::accept($patient, 'privacy_notice');
        });

        $request->session()->forget(['first.invite', 'first.password']);
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth.started_at', time());
        $request->session()->put('auth.last_seen', time());
        Audit::log('auth.first_access', 'user:'.$user->id, $patient->id);

        return redirect()->route('home')->with('status', 'Bem-vindo ao Bioma CD!');
    }

    private function invite(Request $request): ?Invitation
    {
        $id = $request->session()->get('first.invite');
        $invite = $id ? Invitation::where('id', $id)->where('kind', 'patient')->whereNull('used_at')->where('expires_at', '>', now())->first() : null;
        if ($invite && (! $invite->patient || ! $invite->patient->user)) {
            return null;
        }

        return $invite;
    }
}
