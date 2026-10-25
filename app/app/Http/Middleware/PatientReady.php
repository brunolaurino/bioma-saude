<?php

namespace App\Http\Middleware;

use App\Security\Consents;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Nova versão do aviso de privacidade com mudança relevante pede nova ciência na abertura seguinte. */
class PatientReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $patient = $request->user()->patient;

        if (Consents::needsNoticeAcceptance($patient) && ! $request->routeIs('notice.*', 'logout', 'privacy.notice')) {
            return redirect()->route('notice.review');
        }

        return $next($request);
    }
}
