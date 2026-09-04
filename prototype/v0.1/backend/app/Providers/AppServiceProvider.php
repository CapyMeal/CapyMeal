<?php

namespace App\Providers;

use App\Http\Middleware\RestoreConfiguredSessionSameSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftSocialiteProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Captura el session.same_site real (desde config/session.php,
        // SESSION_SAME_SITE en producción) antes de que
        // EnsureFrontendRequestsAreStateful de Sanctum lo pise a "lax" en
        // cada request -- ver el comentario en RestoreConfiguredSessionSameSite.
        RestoreConfiguredSessionSameSite::$configuredSameSite = config('session.same_site');
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

        // Socialite no trae un driver nativo de Microsoft (sólo Facebook,
        // GitHub, Google, LinkedIn, Bitbucket, Slack, Twitter) -- este
        // listener lo registra vía el paquete de la comunidad
        // socialiteproviders/microsoft. Google no necesita esto porque su
        // driver ya viene incluido en laravel/socialite.
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', MicrosoftSocialiteProvider::class);
        });
    }
}
