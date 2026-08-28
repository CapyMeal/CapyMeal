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
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
