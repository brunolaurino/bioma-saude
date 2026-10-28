<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Profissional só passa daqui com 2FA configurado e confirmado nesta sessão. */
class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isProfessional()) {
            if (! $user->hasTwoFactor()) {
                return redirect()->route('2fa.setup');
            }
            if (! $request->session()->get('auth.2fa_passed')) {
                return redirect()->route('2fa.challenge');
            }
        }

        return $next($request);
    }
}
