<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        // Absoluta y seteada aparte de APP_URL a propósito: el puerto
        // interno del contenedor (8000) no es el puerto real mapeado al
        // host (8080), así que derivarla de APP_URL generaría una URL que
        // el navegador nunca puede alcanzar. Tiene que coincidir carácter
        // por carácter con lo registrado en Google Cloud Console.
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        // Misma razón que 'redirect' en 'google' -- ver comentario arriba.
        // Tiene que coincidir carácter por carácter con lo registrado en el
        // App registration de Azure/Microsoft Entra.
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
        // 'common' = cuentas personales + de trabajo/escuela (multi-tenant).
        // CapyMeal es para cualquier persona, no sólo cuentas corporativas.
        'tenant' => env('MICROSOFT_TENANT', 'common'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
