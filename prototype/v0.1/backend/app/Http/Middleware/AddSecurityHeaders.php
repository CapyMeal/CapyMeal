<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// La API no sirve HTML, así que no necesita su propia Content-Security-Policy
// (esa vive en vercel.json, del lado del frontend, que es quien renderiza) --
// estos son los headers de seguridad que sí tiene sentido mandar en cualquier
// response, JSON incluido: no vienen gratis con FrankenPHP/Caddy ni con
// Render, hay que agregarlos a mano.
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains');

        return $response;
    }
}
