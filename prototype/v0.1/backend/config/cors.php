<?php

$frontendUrl = env('FRONTEND_URL');

$allowedOrigins = ['http://localhost:5173', 'http://localhost:5174'];

if ($frontendUrl) {
    $allowedOrigins[] = rtrim($frontendUrl, '/');
}

return [
    'paths'                    => ['api/*'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => false,
];
