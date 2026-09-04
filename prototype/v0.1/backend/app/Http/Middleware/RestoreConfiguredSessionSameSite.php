<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Sanctum's EnsureFrontendRequestsAreStateful pisa session.same_site a
// "lax" en TODA request que pase por el grupo "api" (statefulApi()), sin
// ninguna forma de desactivarlo -- ver
// vendor/laravel/sanctum/.../EnsureFrontendRequestsAreStateful::configureSecureCookieSessions().
// Eso rompe la cookie de sesión cross-site que CapyMeal necesita en
// producción (frontend en Vercel, backend en Render, dominios distintos de
// verdad): SameSite=Lax no viaja en un fetch/XHR cross-site, así que la
// cookie que login() acaba de setear nunca vuelve en el siguiente request.
// Este middleware corre después de Sanctum en el grupo "api"
// (bootstrap/app.php) y restaura el valor real (SESSION_SAME_SITE=none en
// producción) justo antes de que StartSession escriba el Set-Cookie de la
// respuesta -- el valor original se captura en AppServiceProvider::register(),
// antes de que Sanctum llegue a pisarlo.
class RestoreConfiguredSessionSameSite
{
    public static ?string $configuredSameSite = null;

    public function handle(Request $request, Closure $next)
    {
        config(['session.same_site' => static::$configuredSameSite]);

        return $next($request);
    }
}
