<?php

use App\Http\Middleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware as MiddlewareConfig;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (MiddlewareConfig $middleware): void {
        // Só o Caddy, na rede interna, fala com o app.
        $middleware->trustProxies(at: '*');
        $middleware->append(Middleware\SecurityHeaders::class);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $r) => $r->user()?->isPatient() ? route('home') : route('pro.dashboard'));
        $middleware->alias([
            'user.type' => Middleware\EnsureUserType::class,
            'timeouts' => Middleware\SessionTimeouts::class,
            '2fa' => Middleware\RequireTwoFactor::class,
            'reauth' => Middleware\RequireReauth::class,
            'module' => Middleware\PatientModule::class,
            'patient.ready' => Middleware\PatientReady::class,
            'setup' => Middleware\SetupComplete::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Nada de stack trace ou dado de requisição nos logs além do necessário.
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password', 'code', 'text', 'note']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
