<?php

namespace App\Http\Middleware;

use App\Security\Modules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * module:fotos — módulo desligado para o paciente responde 403 na API e nas telas,
 * não só some do menu. Vários módulos separados por "|" passam se qualquer um estiver ligado.
 */
class PatientModule
{
    public function handle(Request $request, Closure $next, string $modules): Response
    {
        $patient = $request->user()->patient;

        foreach (explode('|', $modules) as $module) {
            if (Modules::enabled($module, $patient->id)) {
                return $next($request);
            }
        }

        abort(403, 'Esta função não está disponível no seu acompanhamento.');
    }
}
