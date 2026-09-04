<?php

$frontendUrl = env('FRONTEND_URL');

// 4173 es el puerto default de "vite preview" (necesario para probar la PWA:
// el service worker no se registra corriendo "vite dev" en 5173/5174).
$allowedOrigins = ['http://localhost:5173', 'http://localhost:5174', 'http://localhost:4173'];

// "*" nunca se acepta acá, aunque llegue seteado así por env: preferimos
// que el frontend real se quede afuera (visible, se nota al toque) antes
// que abrir CORS a cualquier origen por un fallback mal configurado.
if ($frontendUrl && $frontendUrl !== '*') {
    $allowedOrigins[] = rtrim($frontendUrl, '/');
}

return [
    // "sanctum/csrf-cookie" no vive bajo "api/*" -- sin listarla acá el
    // navegador bloquea el fetch cross-site que la pide (no llegan los
    // headers Access-Control-Allow-*), y sin esa cookie previa el backend
    // rechaza login/register con 419 por CSRF.
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    // true a propósito: el modo cookie de Sanctum (auth por sesión en vez
    // de bearer token) necesita que el navegador mande/reciba la cookie
    // httpOnly en requests cross-site. Seguro acá porque allowed_origins
    // nunca incluye "*" (ver arriba), un origen explícito + credentials es
    // la combinación que exige el spec de CORS.
    'supports_credentials' => true,
];
