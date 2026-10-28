<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança em toda resposta. A CSP não libera script nem estilo inline, nem
 * nenhuma origem externa: fontes, ícones e scripts saem do próprio ambiente.
 *
 * A única exceção é o hash do estilo da barra de progresso do Turbo 8.0.12 (navegação sem recarregar).
 * Se o Turbo for atualizado, o navegador mostra o novo hash no console; troque-o aqui.
 */
class SecurityHeaders
{
    private const TURBO_STYLE = "'sha256-WAyOw4V+FqDc35lQPyRADLBWbuNK8ahvYEaQIYF1+Ps='";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'Content-Security-Policy' => "default-src 'self'; script-src 'self'; style-src 'self' ".self::TURBO_STYLE."; img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; manifest-src 'self'; worker-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'none'; object-src 'none'",
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(self), microphone=(), geolocation=(), payment=(), usb=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'X-Frame-Options' => 'DENY',
        ];
        foreach ($headers as $k => $v) {
            $response->headers->set($k, $v);
        }

        // Página com dado de saúde não fica em cache do navegador nem de proxy.
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
