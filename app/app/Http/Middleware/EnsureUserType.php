<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** user.type:patient | user.type:professional | user.type:master */
class EnsureUserType
{
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user();

        $ok = match ($type) {
            'patient' => $user?->isPatient() && $user->patient !== null,
            'professional' => $user?->isProfessional(),
            'master' => $user?->isMaster(),
            default => false,
        };

        if (! $ok) {
            if ($user && $type !== 'master') {
                return redirect()->route($user->isPatient() ? 'home' : 'pro.dashboard');
            }
            abort(403, 'Esta área é só para o profissional master.');
        }

        if ($user->status !== 'active') {
            auth()->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->with('error', 'Esta conta não está ativa.');
        }

        return $next($request);
    }
}
