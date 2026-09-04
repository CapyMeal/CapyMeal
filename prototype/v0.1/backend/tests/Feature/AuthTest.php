<?php

namespace Tests\Feature;

use App\Http\Middleware\RestoreConfiguredSessionSameSite;
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
        $response->assertJsonStructure(['user' => ['id', 'name', 'email']]);
        $response->assertJsonMissingPath('user.password');
        $response->assertJsonMissingPath('token');

        $this->assertDatabaseHas('users', ['email' => 'mercedes@example.com']);
        $this->assertAuthenticated();
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
        $response->assertJsonStructure(['user']);
        $response->assertJsonMissingPath('token');
        $this->assertAuthenticated();
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

    public function test_session_cookie_keeps_the_configured_same_site_instead_of_sanctums_forced_lax(): void
    {
        // EnsureFrontendRequestsAreStateful de Sanctum pisa session.same_site
        // a "lax" en toda request stateful, sin forma de desactivarlo -- ver
        // el comentario en RestoreConfiguredSessionSameSite. Producción
        // necesita "none" de verdad (frontend y backend en dominios cruzados
        // reales); acá se usa "strict" en vez de "lax" (que ya es el default
        // y no probaría nada) para confirmar que el mecanismo de restauración
        // funciona para cualquier valor distinto del que Sanctum fuerza.
        config(['session.same_site' => 'strict']);
        RestoreConfiguredSessionSameSite::$configuredSameSite = 'strict';

        $response = $this->postJson('/api/login', [
            'email' => 'no-existe@example.com',
            'password' => 'lo-que-sea',
        ]);

        $sessionCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($sessionCookie);
        $this->assertSame('strict', $sessionCookie->getSameSite());
    }

    public function test_login_does_not_revoke_a_pre_existing_bearer_token(): void
    {
        // CapyMeal migró de bearer tokens a cookie de sesión (login() ya no
        // emite tokens nuevos), pero un token viejo emitido antes de la
        // migración tiene que seguir sirviendo hasta que se revoque a mano --
        // loguearse de nuevo (ahora por cookie) no debería invalidarlo.
        $user = User::factory()->create([
            'email' => 'mercedes@example.com',
            'password' => Hash::make('capymeal123'),
        ]);
        $existingToken = $user->createToken('capymeal')->plainTextToken;

        $this->postJson('/api/login', [
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
        ])->assertOk();

        auth()->forgetGuards();

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
