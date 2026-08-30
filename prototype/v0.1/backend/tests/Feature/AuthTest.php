<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // /register y /login tienen throttle:6,1. El cache "array" de
        // testing vive durante todo el proceso del suite, así que sin
        // esto los intentos se acumulan entre tests y terminan en 429.
        Cache::flush();
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Mercedes',
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
            'password_confirmation' => 'capymeal123',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
        $response->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', ['email' => 'mercedes@example.com']);
    }

    public function test_registration_fails_with_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Mercedes',
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
            'password_confirmation' => 'otra-cosa',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('users', ['email' => 'mercedes@example.com']);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'mercedes@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Otra Mercedes',
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
            'password_confirmation' => 'capymeal123',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, User::where('email', 'mercedes@example.com')->count());
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        User::factory()->create([
            'email' => 'mercedes@example.com',
            'password' => Hash::make('capymeal123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'mercedes@example.com',
            'password' => Hash::make('capymeal123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'mercedes@example.com',
            'password' => 'contraseña-incorrecta',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'no-existe@example.com',
            'password' => 'lo-que-sea',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_does_not_revoke_sessions_on_other_devices(): void
    {
        // CapyMeal es una PWA pensada para usarse desde varios dispositivos
        // (celular + compu) -- loguearse en uno no debería desloguear al
        // otro sin aviso.
        $user = User::factory()->create([
            'email' => 'mercedes@example.com',
            'password' => Hash::make('capymeal123'),
        ]);
        $existingToken = $user->createToken('capymeal')->plainTextToken;

        $this->postJson('/api/login', [
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
        ])->assertOk();

        $this->withHeader('Authorization', "Bearer {$existingToken}")
            ->getJson('/api/me')
            ->assertOk();
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('capymeal')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertNoContent();

        // Sin esto, la segunda request reusaría el guard ya resuelto en la
        // primera llamada de este mismo método en vez de validar de nuevo
        // contra el token (ya borrado) -- mismo caso que en AccountDeletionTest.
        auth()->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertStatus(401);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Mercedes']);
        $token = $user->createToken('capymeal')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('name', 'Mercedes');
    }

    public function test_unauthenticated_request_to_me_is_rejected(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_user_can_update_avatar(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('capymeal')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/me/avatar', ['avatar' => 'capy2']);

        $response->assertOk();
        $response->assertJsonPath('avatar', 'capy2');
        $this->assertSame('capy2', $user->fresh()->avatar);
    }

    public function test_user_can_clear_avatar(): void
    {
        $user = User::factory()->create(['avatar' => 'capy1']);
        $token = $user->createToken('capymeal')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/me/avatar', ['avatar' => null]);

        $response->assertOk();
        $response->assertJsonPath('avatar', null);
        $this->assertNull($user->fresh()->avatar);
    }

    public function test_update_avatar_rejects_invalid_value(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('capymeal')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/me/avatar', ['avatar' => 'no-existe']);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_request_to_update_avatar_is_rejected(): void
    {
        $this->putJson('/api/me/avatar', ['avatar' => 'capy1'])->assertStatus(401);
    }
}
