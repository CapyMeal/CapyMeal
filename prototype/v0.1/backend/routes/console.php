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
//
// ->dailyAt() en vez de ->daily() a secas: Render no tiene cron jobs en el
// plan free, así que nada llama a "schedule:run" cada minuto como
// esperaría Laravel. En su lugar, un workflow de GitHub Actions
// (.github/workflows/scheduler.yml) golpea SchedulerController una vez al
// día a una hora fija -- la tarea sólo se considera "due" en la ventana de
// ese mismo minuto, así que el horario de acá tiene que coincidir
// exactamente con el cron del workflow o esto nunca corre.
Schedule::command('sanctum:prune-expired')->dailyAt('06:10');
