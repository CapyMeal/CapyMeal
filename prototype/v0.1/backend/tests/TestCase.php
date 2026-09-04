<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum sólo trata una request como "de la SPA" (habilitando sesión
        // y cookie en vez de sólo bearer token) si trae un Referer/Origin que
        // matchee sanctum.stateful -- en producción el navegador lo manda
        // solo, acá hay que simularlo para que login/register/exchange (que
        // ahora dependen de $request->session()) no exploten con "Session
        // store not set on request." Se fuerza el config en vez de confiar
        // en el default de config/sanctum.php: Dotenv carga el .env real del
        // filesystem para cualquier variable que phpunit.xml no pisa
        // explícitamente, así que si alguien define SANCTUM_STATEFUL_DOMAINS
        // en el .env de desarrollo (sin "localhost" pelado, por los puertos
        // específicos del frontend) los tests quedarían rotos por una razón
        // ajena a lo que testean.
        config(['sanctum.stateful' => ['localhost']]);
        $this->withHeader('Referer', 'http://localhost');

        // Al quedar "stateful" (arriba), Sanctum suma su middleware de CSRF a
        // toda request que mute estado -- correcto en producción (browser
        // real, cookie XSRF-TOKEN real), pero acá no hay forma liviana de
        // simular el handshake completo (pedir /sanctum/csrf-cookie, leer la
        // cookie, mandarla de vuelta) sin ensuciar cada test. Se desactiva
        // sólo para tests, igual que ya pasaba antes de este cambio (las
        // rutas de api.php nunca pasaron por CSRF hasta ahora) -- lo que se
        // testea acá es la lógica de los controllers, no el middleware de
        // CSRF de Sanctum, que es código de framework ya probado aparte.
        config(['sanctum.middleware.validate_csrf_token' => null]);
    }
}
