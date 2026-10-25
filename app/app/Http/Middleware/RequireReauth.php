<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Exportar dados, trocar senha e excluir conta pedem a senha de novo (vale 10 minutos). */
class RequireReauth
{
    public function handle(Request $request, Closure $next): Response
    {
        $at = (int) $request->session()->get('auth.reauth_at', 0);

        if (time() - $at > config('bioma.session.reauth_minutes') * 60) {
            if ($request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            } else {
                $request->session()->put('url.intended', url()->previous());
            }

            return redirect()->route('reauth');
        }

        return $next($request);
    }
}
