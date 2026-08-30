<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Las rutas de Google están throttled igual que login/register --
        // sin esto los intentos se acumulan entre tests del mismo proceso.
        Cache::flush();
    }

    public function test_new_user_is_created_via_google(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-1',
            'name' => 'Mercedes',
            'email' => 'mercedes@example.com',
        ]));

        $this->get('/api/auth/google/callback')->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'mercedes@example.com',
            'google_id' => 'g-1',
            'password' => null,
        ]);

        $user = User::where('email', 'mercedes@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_existing_google_user_logs_in_again_without_duplicating(): void
    {
        $user = User::factory()->create(['google_id' => 'g-1', 'password' => null]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-1',
            'name' => $user->name,
            'email' => $user->email,
        ]));

        $this->get('/api/auth/google/callback');

        $this->assertSame(1, User::where('google_id', 'g-1')->count());
    }

    public function test_google_login_auto_links_existing_password_account(): void
    {
        $user = User::factory()->create([
            'email' => 'mercedes@example.com',
            'password' => Hash::make('capymeal123'),
            'google_id' => null,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-1',
            'name' => $user->name,
            'email' => 'mercedes@example.com',
        ]));

        $this->get('/api/auth/google/callback');

        $user->refresh();
        $this->assertSame('g-1', $user->google_id);
        $this->assertNotNull($user->password);
    }

    public function test_exchange_code_redeems_for_token_and_user(): void
    {
        $user = User::factory()->create(['google_id' => 'g-1', 'password' => null]);
        Cache::put('google-auth-exchange:test-code', ['user_id' => $user->id], now()->addMinute());

        $response = $this->postJson('/api/auth/google/exchange', ['code' => 'test-code']);

        $response->assertOk();
        $response->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
        $response->assertJsonPath('user.email', $user->email);
    }

    public function test_exchange_code_cannot_be_reused(): void
    {
        $user = User::factory()->create(['google_id' => 'g-1', 'password' => null]);
        Cache::put('google-auth-exchange:test-code', ['user_id' => $user->id], now()->addMinute());

        $this->postJson('/api/auth/google/exchange', ['code' => 'test-code'])->assertOk();
        $this->postJson('/api/auth/google/exchange', ['code' => 'test-code'])->assertStatus(422);
    }

    public function test_expired_or_unknown_exchange_code_fails(): void
    {
        $this->postJson('/api/auth/google/exchange', ['code' => 'never-existed'])->assertStatus(422);
    }

    public function test_login_with_password_against_google_only_account_fails_with_generic_message(): void
    {
        $user = User::factory()->create(['password' => null, 'google_id' => 'g-1']);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'lo-que-sea',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.email.0', 'El email o la contraseña son incorrectos.');
    }

    public function test_google_only_account_can_be_deleted_without_password(): void
    {
        $user = User::factory()->create(['password' => null, 'google_id' => 'g-1']);
        $token = $user->createToken('capymeal')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/me')
            ->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
