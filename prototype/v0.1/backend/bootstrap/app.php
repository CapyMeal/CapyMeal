<?php

use App\Http\Middleware\RestoreConfiguredSessionSameSite;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Habilita que Sanctum autentique por cookie de sesión (en vez de
        // bearer token) las requests que vengan de un dominio listado en
        // SANCTUM_STATEFUL_DOMAINS -- cualquier otro cliente sigue cayendo
        // al bearer token de siempre, auth:sanctum sirve para ambos casos
        // sin tocar las rutas.
        $middleware->statefulApi();

        // Sanctum pisa session.same_site a "lax" en cada request stateful,
        // sin forma de desactivarlo (ver el comentario en
        // RestoreConfiguredSessionSameSite) -- se restaura después, en el
        // mismo grupo "api", el valor real de SESSION_SAME_SITE.
        $middleware->api(append: [RestoreConfiguredSessionSameSite::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // El mensaje por defecto de Laravel para esto es "Unauthenticated."
        // (hardcodeado, no pasa por los archivos de lang) -- se traduce
        // acá explícitamente para que no aparezca en inglés en la app.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tu sesión expiró. Volvé a iniciar sesión.',
                ], 401);
            }
        });

        Integration::handles($exceptions);
    })->create();
