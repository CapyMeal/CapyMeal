<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Two\User as SocialiteOAuth2User;

class MicrosoftAuthController extends SocialAuthController
{
    protected function driver(): string
    {
        return 'microsoft';
    }

    protected function providerIdColumn(): string
    {
        return 'microsoft_id';
    }

    // El provider de Socialite mapea getEmail() a "userPrincipalName", que
    // en cuentas de trabajo/escuela es sólo el nombre de inicio de sesión --
    // un admin de esa organización puede asignarlo sin que pruebe ser dueño
    // de ningún buzón real (a diferencia de Google, que sólo devuelve
    // emails verificados). "mail" es el buzón real de Microsoft Graph;
    // preferirlo reduce el riesgo de auto-vincular una cuenta existente con
    // un UPN que no prueba nada. Cuentas personales (outlook.com/hotmail.com)
    // no suelen traer "mail" separado, de ahí el fallback.
    //
    // getRaw() no forma parte del contrato Contracts\User (sólo expone
    // getId/getNickname/getName/getEmail/getAvatar) -- el driver de
    // Microsoft, como todos los que usamos, es OAuth2 y siempre devuelve
    // Two\User en la práctica, que sí lo tiene. El instanceof deja que
    // PHPStan angoste el tipo en vez de forzarlo con un cast ciego.
    protected function extractEmail(SocialiteUser $socialiteUser): ?string
    {
        if (! $socialiteUser instanceof SocialiteOAuth2User) {
            return $socialiteUser->getEmail();
        }

        return $socialiteUser->getRaw()['mail'] ?? $socialiteUser->getEmail();
    }
}
