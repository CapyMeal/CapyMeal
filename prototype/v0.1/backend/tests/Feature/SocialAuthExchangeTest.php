<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// Cubre todo el comportamiento de SocialAuthController/AuthController que no
// depende de qué proveedor creó la cuenta (exchange, login con password
// contra una cuenta social-only, borrado sin password) -- parametrizado
// sobre Google y Microsoft en vez de duplicar estos mismos tests en
// GoogleAuthTest/MicrosoftAuthTest.
class SocialAuthExchangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public static function providers(): array
    {
        return [
            'google' => ['google', 'google_id'],
            'microsoft' => ['microsoft', 'microsoft_id'],
        ];
    }

    #[DataProvider('providers')]
    public function test_exchange_code_redeems_for_a_session_and_user(string $driver, string $column): void
    {
        $user = User::factory()->create([$column => 'p-1', 'password' => null]);
        Cache::put('social-auth-exchange:test-code', ['user_id' => $user->id], now()->addMinute());

        $response = $this->postJson("/api/auth/{$driver}/exchange", ['code' => 'test-code']);

        $response->assertOk();
        $response->assertJsonStructure(['user' => ['id', 'name', 'email']]);
        $response->assertJsonMissingPath('token');
        $response->assertJsonPath('user.email', $user->email);
        $this->assertAuthenticated();
    }

    #[DataProvider('providers')]
    public function test_exchange_code_cannot_be_reused(string $driver, string $column): void
    {
        $user = User::factory()->create([$column => 'p-1', 'password' => null]);
        Cache::put('social-auth-exchange:test-code', ['user_id' => $user->id], now()->addMinute());

        $this->postJson("/api/auth/{$driver}/exchange", ['code' => 'test-code'])->assertOk();
        $this->postJson("/api/auth/{$driver}/exchange", ['code' => 'test-code'])->assertStatus(422);
    }

    #[DataProvider('providers')]
    public function test_expired_or_unknown_exchange_code_fails(string $driver): void
    {
        $this->postJson("/api/auth/{$driver}/exchange", ['code' => 'never-existed'])->assertStatus(422);
    }

    #[DataProvider('providers')]
    public function test_login_with_password_against_social_only_account_fails_with_generic_message(string $driver, string $column): void
    {
        $user = User::factory()->create(['password' => null, $column => 'p-1']);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'lo-que-sea',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.email.0', 'El email o la contraseña son incorrectos.');
    }

    #[DataProvider('providers')]
    public function test_social_only_account_can_be_deleted_without_password(string $driver, string $column): void
    {
        $user = User::factory()->create(['password' => null, $column => 'p-1']);
        $token = $user->createToken('capymeal')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/me')
            ->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
