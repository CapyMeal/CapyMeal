<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

// El frontend manda X-Locale: es|en en cada request (ver httpClient.js) --
// acá se lee ese header y se fija el locale de la app para toda la
// duración del request, antes de que corran validación/controllers/
// notificaciones, que son quienes efectivamente resuelven strings
// traducidos vía __()/trans(). Cualquier valor que no sea exactamente "es"
// o "en" (header ausente, typo, otro idioma) deja el locale tal cual está
// configurado por default (config('app.locale')) en vez de fallar.
class SetLocaleFromHeader
{
    private const SUPPORTED_LOCALES = ['es', 'en'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('X-Locale');

        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
