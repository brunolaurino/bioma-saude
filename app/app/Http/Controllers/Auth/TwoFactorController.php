<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Security\Audit;
use App\Security\TwoFactor;
use Illuminate\Http\Request;

/** 2FA obrigatório para profissionais (TOTP), com 10 códigos de recuperação. */
class TwoFactorController extends Controller
{
    public function setup(Request $request)
    {
        $user = $request->user();
        if ($user->hasTwoFactor()) {
            return redirect()->route('2fa.challenge');
        }

        $secret = $request->session()->get('2fa.pending') ?? TwoFactor::newSecret();
        $request->session()->put('2fa.pending', $secret);

        return view('auth.2fa-setup', [
            'qr' => TwoFactor::qrSvg($user->email, $secret, 'Saúde App'),
            'secret' => trim(chunk_split($secret, 4, ' ')),
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:20']], ['code.required' => 'Digite o código de 6 dígitos.']);
        $secret = $request->session()->get('2fa.pending');

        if (! $secret || ! TwoFactor::verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Código incorreto. Confira o horário do celular e digite o código atual.']);
        }

        [$plain, $hashes] = TwoFactor::recoveryCodes();
        $user = $request->user();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $hashes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('2fa.pending');
        $request->session()->put('auth.2fa_passed', true);
        $request->session()->flash('2fa.codes', $plain);
        Audit::log('auth.2fa.enabled', 'user:'.$user->id);

        return redirect()->route('2fa.codes');
    }

    public function codes(Request $request)
    {
        $codes = $request->session()->get('2fa.codes');
        if (! $codes) {
            return redirect()->route('pro.dashboard');
        }

        return view('auth.2fa-codes', ['codes' => $codes]);
    }

    public function challenge(Request $request)
    {
        $user = $request->user();
        if (! $user->isProfessional() || $request->session()->get('auth.2fa_passed')) {
            return redirect()->route('root');
        }
        if (! $user->hasTwoFactor()) {
            return redirect()->route('2fa.setup');
        }

        return view('auth.2fa-challenge', ['recovery' => $request->boolean('recuperacao')]);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:64']], ['code.required' => 'Digite o código.', 'code.max' => 'Digite o código de 6 dígitos do app autenticador.']);
        $user = $request->user();
        $code = (string) $request->input('code');

        $ok = strlen(preg_replace('/\D/', '', $code)) === 6 && ! str_contains($code, '-')
            ? TwoFactor::verify($user->two_factor_secret, $code)
            : TwoFactor::useRecoveryCode($user, $code);

        if (! $ok) {
            Audit::log('auth.2fa.fail', 'user:'.$user->id);
            // Erro comum: colar a chave do 2FA (texto longo) no lugar do código de 6 dígitos.
            $looksLikeKey = preg_match('/^[A-Z2-7]{16,}$/', strtoupper(preg_replace('/\s+/', '', $code)));

            return back()->withErrors(['code' => $looksLikeKey
                ? 'Isso é a chave do 2FA, não o código. Cadastre a chave no app autenticador e digite os 6 dígitos que ele mostra.'
                : 'Código incorreto. Confira se o relógio do celular está no automático e use o código que está aparecendo agora.']);
        }

        $request->session()->regenerate();
        $request->session()->put('auth.2fa_passed', true);
        Audit::log('auth.2fa.pass', 'user:'.$user->id);

        return redirect()->intended(route('pro.dashboard'));
    }
}
