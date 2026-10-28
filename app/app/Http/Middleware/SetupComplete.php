<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** O master passa pelo assistente de primeira configuração antes de qualquer tela do painel. */
class SetupComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Settings::get('setup_completed') !== '1' && $request->user()?->isMaster() && ! $request->routeIs('pro.setup*')) {
            return redirect()->route('pro.setup');
        }

        return $next($request);
    }
}
