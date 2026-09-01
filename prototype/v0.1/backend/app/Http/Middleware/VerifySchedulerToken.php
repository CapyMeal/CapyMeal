<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Protege el endpoint que dispara el scheduler de Laravel (ver
// SchedulerController) -- no es un usuario logueado el que llama acá, es el
// workflow de GitHub Actions (routes/console.php explica por qué hace
// falta esto: Render no tiene cron jobs en el plan free). hash_equals()
// evita timing attacks al comparar el token.
class VerifySchedulerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('app.scheduler_token');
        $given = (string) $request->bearerToken();

        if (blank($expected) || ! hash_equals($expected, $given)) {
            abort(401);
        }

        return $next($request);
    }
}
