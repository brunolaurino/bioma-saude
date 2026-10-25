<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Security\Audit;
use App\Security\BlindIndex;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.login', ['who' => $request->query('perfil') === 'profissional' ? 'pro' : 'pac', 'lead' => Settings::leadName()]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:200'],
        ], ['email.required' => 'Digite seu e-mail.', 'password.required' => 'Digite sua senha.']);

        $hash = BlindIndex::email($data['email']);
        $key = 'login-account|'.$hash;

        // 5 tentativas por minuto por conta (o limite por IP fica na rota).
        if (RateLimiter::tooManyAttempts($key, config('bioma.login.per_minute'))) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Muitas tentativas. Espere um minuto e tente de novo.']);
        }

        $user = User::where('email_hash', $hash)->first();

        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            Audit::record('anônimo', 'auth.login.locked', 'user:'.$user->id, null, [], $user->id);

            return back()->withInput($request->only('email'))->withErrors(['email' => 'Conta bloqueada por excesso de tentativas. Tente de novo depois de '.$user->locked_until->setTimezone(config('bioma.timezone'))->format('H\hi').' ou recupere a senha.']);
        }

        // Compara mesmo sem conta, para o tempo de resposta não revelar se o e-mail existe.
        $ok = Hash::check($data['password'], $user?->password ?: Cache::rememberForever('auth.dummy_hash', fn () => Hash::make(Str::random(40))));

        if (! $user || ! $ok || $user->status !== 'active' || ! $user->password) {
            RateLimiter::hit($key, 60);
            if ($user) {
                $this->registerFailure($user);
            }
            Audit::record('anônimo', 'auth.login.fail', $user ? 'user:'.$user->id : 'user:desconhecido', null, [], $user?->id);

            return back()->withInput($request->only('email'))->withErrors(['email' => 'E-mail ou senha incorretos.']);
        }

        RateLimiter::clear($key);
        $user->forceFill(['failed_logins' => 0, 'locked_until' => null, 'last_login_at' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth.started_at', time());
        $request->session()->put('auth.last_seen', time());
        Audit::log('auth.login', 'user:'.$user->id, $user->patient?->id);

        if ($user->isProfessional()) {
            return redirect()->route($user->hasTwoFactor() ? '2fa.challenge' : '2fa.setup');
        }

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Audit::log('auth.logout', 'user:'.$request->user()->id);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Você saiu da conta.');
    }

    /** Bloqueio progressivo: a partir de 10 falhas seguidas, 15 min, depois 30, 60… e aviso por e-mail. */
    private function registerFailure(User $user): void
    {
        $fails = $user->failed_logins + 1;
        $lockAfter = config('bioma.login.lock_after');
        $fill = ['failed_logins' => $fails];

        if ($fails >= $lockAfter) {
            $minutes = 15 * (2 ** min(5, intdiv($fails - $lockAfter, $lockAfter)));
            $fill['locked_until'] = now()->addMinutes($minutes);
            if ($fails === $lockAfter) {
                try {
                    Mail::raw("Houve $fails tentativas de entrar na sua conta do Bioma CD com a senha errada. Por segurança, ela ficou bloqueada por $minutes minutos. Se não foi você, troque sua senha pela opção \"Esqueci minha senha\".", function ($m) use ($user) {
                        $m->to($user->email)->subject('Bioma CD: tentativas de acesso à sua conta');
                    });
                } catch (\Throwable $e) {
                    Log::warning('Falha ao enviar aviso de bloqueio de conta.');
                }
            }
        }

        $user->forceFill($fill)->save();
    }
}
