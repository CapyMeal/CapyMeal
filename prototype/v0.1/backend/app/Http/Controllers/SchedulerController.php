<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class SchedulerController extends Controller
{
    // Corre el scheduler de Laravel (routes/console.php) -- el mismo efecto
    // que tendría un cron real invocando "php artisan schedule:run" cada
    // minuto, salvo que acá lo dispara una vez al día el workflow de GitHub
    // Actions (.github/workflows/scheduler.yml). Por eso las tareas
    // registradas en routes/console.php usan ->dailyAt() con un horario
    // fijo que coincide con el cron del workflow, no ->daily() a secas: la
    // ventana en la que Laravel considera una tarea "due" es de un minuto,
    // y si nunca se llama exactamente en esa ventana, la tarea nunca corre.
    public function run(): void
    {
        Artisan::call('schedule:run');
    }
}
