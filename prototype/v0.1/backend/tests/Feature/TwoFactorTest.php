<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // /login y /login/two-factor tienen throttle:6,1 -- mismo motivo
        // que en AuthTest.
        Cache::flush();
    }

    private function bearerToken(User $user): string
    {
        return $user->createToken('capymeal')->plainTextToken;
    }

    public function test_setup_generates_a_pending_secret_and_a_qr_code(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->bearerToken($user))
            ->postJson('/api/two-factor/setup');

        $response->assertOk();
        $response->assertJsonStructure(['secret', 'qrCodeSvg']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $response->json('qrCodeSvg'));

        // Pendiente, todavía no activo -- confirm() es quien lo activa.
        $this->assertNotNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_setup_rejects_when_two_factor_is_already_enabled(): void
    {
        $secret = (new Google2FA)->generateSecretKey();
        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [Hash::make('codigo')],
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->bearerToken($user))
            ->postJson('/api/two-factor/setup')
            ->assertStatus(422);

        // Ni el secreto ni los códigos de recuperación se tocaron.
        $this->assertSame($secret, $user->fresh()->two_factor_secret);
    }

    public function test_confirm_with_a_valid_code_activates_two_factor_and_returns_recovery_codes(): void
    {
        $user = User::factory()->create();
        $token = $this->bearerToken($user);
        $secret = (new Google2FA)->generateSecretKey();
        $user->forceFill(['two_factor_secret' => $secret])->save();

        $validCode = (new Google2FA)->getCurrentOtp($secret);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/two-factor/confirm', ['code' => $validCode]);

        $response->assertOk();
        $recoveryCodes = $response->json('recoveryCodes');
        $this->assertCount(10, $recoveryCodes);

        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertCount(10, $user->two_factor_recovery_codes);
        // Lo que se guarda son hashes, no los códigos en texto plano.
        $this->assertNotEquals($recoveryCodes[0], $user->two_factor_recovery_codes[0]);
    }

    public function test_confirm_with_an_invalid_code_does_not_activate_two_factor(): void
    {
        $user = User::factory()->create();
        $token = $this->bearerToken($user);
        $secret = (new Google2FA)->generateSecretKey();
        $user->forceFill(['two_factor_secret' => $secret])->save();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/two-factor/confirm', ['code' => '000000'])
            ->assertStatus(422);

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_login_with_two_factor_enabled_returns_a_challenge_instead_of_logging_in(): void
    {
        $secret = (new Google2FA)->generateSecretKey();
        $user = User::factory()->create([
            'email' => 'mercedes@example.com',
            'password' => Hash::make('capymeal123'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [Hash::make('recovery-code-1')],
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('twoFactorRequired', true);
        $response->assertJsonStructure(['challenge']);
        $response->assertJsonMissingPath('user');
        $this->assertGuest();
    }

    public function test_login_two_factor_with_a_valid_totp_code_logs_in(): void
    {
        $secret = (new Google2FA)->generateSecretKey();
        $user = User::factory()->create([
            'email' => 'mercedes@example.com',
            'password' => Hash::make('capymeal123'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $challenge = $this->postJson('/api/login', [
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
        ])->json('challenge');

        $response = $this->postJson('/api/login/two-factor', [
            'challenge' => $challenge,
            'code' => (new Google2FA)->getCurrentOtp($secret),
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'csrfToken']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_two_factor_with_a_valid_recovery_code_logs_in_and_consumes_it(): void
    {
        $secret = (new Google2FA)->generateSecretKey();
        User::factory()->create([
            'email' => 'mercedes@example.com',
            'password' => Hash::make('capymeal123'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [Hash::make('un-codigo-de-recuperacion')],
        ]);

        $challenge = $this->postJson('/api/login', [
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
        ])->json('challenge');

        $this->postJson('/api/login/two-factor', [
            'challenge' => $challenge,
            'code' => 'un-codigo-de-recuperacion',
        ])->assertOk();

        $this->assertAuthenticated();

        // El código de recuperación ya se usó -- no puede servir de nuevo
        // para un segundo login.
        auth()->forgetGuards();

        $secondChallenge = $this->postJson('/api/login', [
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
        ])->json('challenge');

        $this->postJson('/api/login/two-factor', [
            'challenge' => $secondChallenge,
            'code' => 'un-codigo-de-recuperacion',
        ])->assertStatus(422);
    }

    public function test_login_two_factor_with_an_invalid_code_fails(): void
    {
        $secret = (new Google2FA)->generateSecretKey();
        User::factory()->create([
            'email' => 'mercedes@example.com',
            'password' => Hash::make('capymeal123'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $challenge = $this->postJson('/api/login', [
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
        ])->json('challenge');

        $this->postJson('/api/login/two-factor', [
            'challenge' => $challenge,
            'code' => '000000',
        ])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_login_two_factor_with_an_expired_or_unknown_challenge_fails(): void
    {
        $this->postJson('/api/login/two-factor', [
            'challenge' => 'no-existe-este-challenge',
            'code' => '123456',
        ])->assertStatus(422);
    }

    public function test_disable_requires_the_correct_password(): void
    {
        $secret = (new Google2FA)->generateSecretKey();
        $user = User::factory()->create([
            'password' => Hash::make('capymeal123'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [Hash::make('codigo')],
        ]);
        $token = $this->bearerToken($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/two-factor/disable', ['password' => 'mal'])
            ->assertStatus(422);
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/two-factor/disable', ['password' => 'capymeal123'])
            ->assertNoContent();

        $user->refresh();
        $this->assertFalse($user->hasTwoFactorEnabled());
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
    }
}
