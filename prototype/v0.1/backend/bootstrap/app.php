<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\RestoreConfiguredSessionSameSite;
use App\Http\Middleware\SetLocaleFromHeader;
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

        // IP real de quien usa la app. Sin esto, request->ip() era la del
        // balanceador de Render, y los límites por IP (login 6/min, PDF
        // 10/min) eran un solo contador compartido por todos: unos pocos
        // intentos fallidos de cualquiera bloqueaban a los demás.
        //
        // Las requests llegan por Vercel (vercel.json reenvía /api), que
        // completa X-Forwarded-For con la IP del visitante y pisa lo que
        // haya mandado el cliente; Render agrega la suya detrás. Confiando
        // en toda la cadena, Symfony toma la primera, la que puso Vercel.
        // Límite conocido: quien le pegue directo a Render podría mandar un
        // X-Forwarded-For inventado y esquivar el límite por IP; igual es
        // mejor que un contador global que cualquiera puede agotar.
        // Solo X-Forwarded-For: Host y Proto se dejan como los ve Render,
        // para no cambiar las URLs que arma la app (OAuth, mails).
        $middleware->trustProxies(
            at: ['0.0.0.0/0', '::/0'],
            headers: Request::HEADER_X_FORWARDED_FOR,
        );

        // Lee el header X-Locale que manda el frontend en cada request (ver
        // httpClient.js) y fija el locale de la app antes de que corra
        // cualquier otra cosa del grupo "api" -- tiene que ir prepend, no
        // append, porque validación/controllers/notificaciones ya resuelven
        // strings traducidos vía __()/trans() apenas arrancan.
        $middleware->api(prepend: [SetLocaleFromHeader::class]);

        // Sanctum pisa session.same_site a "lax" en cada request stateful,
        // sin forma de desactivarlo (ver el comentario en
        // RestoreConfiguredSessionSameSite) -- se restaura después, en el
        // mismo grupo "api", el valor real de SESSION_SAME_SITE.
        $middleware->api(append: [RestoreConfiguredSessionSameSite::class]);

        // Headers de seguridad en toda respuesta de la API -- no vienen
        // gratis con FrankenPHP/Caddy ni con Render, hay que agregarlos a
        // mano (ver AddSecurityHeaders).
        $middleware->api(append: [AddSecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // El mensaje por defecto de Laravel para esto es "Unauthenticated."
        // (hardcodeado, no pasa por los archivos de lang) -- se traduce
        // acá explícitamente para que no aparezca en inglés en la app.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.session_expired'),
                ], 401);
            }
        });

        Integration::handles($exceptions);
    })->create();
