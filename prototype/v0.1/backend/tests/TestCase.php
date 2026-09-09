<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    // Cuerpo de la respuesta fake de Have I Been Pwned (ver setUp()) --
    // vacío por default, que Password::uncompromised() interpreta como
    // "ningún hash coincide" (contraseña no filtrada). Los tests que
    // necesitan simular una contraseña sí filtrada pisan este valor antes
    // de la request, en vez de volver a llamar Http::fake(): el mock de
    // Http respeta el primer fake registrado para una URL, no el último,
    // así que un segundo Http::fake() en el test nunca ganaría contra
    // este.
    protected string $hibpFakeResponseBody = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Password::uncompromised() (ver AuthController/PasswordResetController)
        // le pega de verdad a la API de Have I Been Pwned -- sin este fake,
        // cada test que registra un usuario dependería de una red externa
        // real (lento y flaky en CI). El callback lee $hibpFakeResponseBody
        // recién al momento de la request (no al registrar el fake), así
        // que un test puede cambiar ese valor antes de llamar al endpoint
        // real y el mismo fake responde distinto sin tener que re-registrar
        // nada.
        Http::fake([
            'api.pwnedpasswords.com/*' => fn () => Http::response($this->hibpFakeResponseBody, 200),
        ]);

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
