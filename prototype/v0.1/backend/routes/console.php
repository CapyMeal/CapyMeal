<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sin esto, un token expirado (login() ya no revoca los viejos -- ver
// AuthController) queda como fila muerta en personal_access_tokens para
// siempre si esa cuenta nunca vuelve a loguearse.
Schedule::command('sanctum:prune-expired')->daily();
