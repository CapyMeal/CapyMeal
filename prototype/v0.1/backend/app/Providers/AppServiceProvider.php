<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gratis en dev/test, no afecta producción -- detecta accesos a
        // relaciones sin cargar (N+1 futuros a medida que el modelo crezca
        // más allá de User/MealEntry) y a atributos inexistentes al
        // momento de escribir el código, no en un log de producción.
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
