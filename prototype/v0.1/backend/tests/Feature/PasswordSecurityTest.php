<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    // Arma el body que el fake de TestCase (ver $hibpFakeResponseBody)
    // devuelve para la próxima request a Have I Been Pwned, replicando el
    // modelo k-Anonymity que usa Password::uncompromised() internamente:
    // SHA1 en mayúsculas, los primeros 5 caracteres van en la URL (eso lo
    // maneja Laravel solo), el resto es lo que tiene que aparecer en el
    // body de la respuesta para contar como "encontrada".
    private function fakeHibpResponseFor(string $password, int $timesSeen = 0): void
    {
        $hash = strtoupper(sha1($password));
        $suffix = substr($hash, 5);

        $this->hibpFakeResponseBody = $timesSeen > 0 ? "{$suffix}:{$timesSeen}" : '';
    }

    public function test_registration_rejects_a_password_found_in_a_known_breach(): void
    {
        $this->fakeHibpResponseFor('capymeal123', timesSeen: 999999);

        $response = $this->postJson('/api/register', [
            'name' => 'Mercedes',
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
            'password_confirmation' => 'capymeal123',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('users', ['email' => 'mercedes@example.com']);
    }

    public function test_registration_accepts_a_password_not_found_in_any_breach(): void
    {
        $this->fakeHibpResponseFor('capymeal123', timesSeen: 0);

        $this->postJson('/api/register', [
            'name' => 'Mercedes',
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
            'password_confirmation' => 'capymeal123',
        ])->assertCreated();
    }

    public function test_password_reset_also_rejects_a_breached_password(): void
    {
        $user = User::factory()->create(['email' => 'mercedes@example.com']);
        $token = Password::createToken($user);

        $this->fakeHibpResponseFor('capymeal123', timesSeen: 5);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
            'password_confirmation' => 'capymeal123',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_with_a_nonexistent_email_still_returns_the_same_generic_message(): void
    {
        // La igualación de timing (ver AuthController::login()) no cambia
        // el comportamiento observable -- este test es la red de
        // seguridad de esa parte: confirma que el mensaje sigue siendo el
        // mismo genérico de siempre. El tiempo en sí no es algo que este
        // suite pueda medir de forma confiable (ni debería).
        $response = $this->postJson('/api/login', [
            'email' => 'no-existe@example.com',
            'password' => 'lo-que-sea',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.email.0', 'El email o la contraseña son incorrectos.');
    }
}
