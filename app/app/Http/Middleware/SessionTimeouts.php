<?php

namespace App\Http\Middleware;

use App\Security\Audit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Painel: expira após 15 min parado e 12 h no total. App do paciente: 30 dias.
 * O cookie de sessão dura 30 dias; este middleware aplica os limites menores do profissional.
 */
class SessionTimeouts
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $session = $request->session();
        $now = time();
        $started = (int) $session->get('auth.started_at', $now);
        $last = (int) $session->get('auth.last_seen', $now);
        $cfg = config('bioma.session');

        [$idle, $absolute] = $user->isProfessional()
            ? [$cfg['pro_idle_minutes'] * 60, $cfg['pro_absolute_minutes'] * 60]
            : [$cfg['patient_days'] * 86400, $cfg['patient_days'] * 86400];

        if ($now - $last > $idle || $now - $started > $absolute) {
            Audit::log('auth.timeout', 'session');
            auth()->logout();
            $session->invalidate();
            $session->regenerateToken();

            return redirect()->route('login')->with('status', 'Sua sessão expirou. Entre de novo para continuar.');
        }

        $session->put('auth.last_seen', $now);
        if (! $session->has('auth.started_at')) {
            $session->put('auth.started_at', $now);
        }

        return $next($request);
    }
}
