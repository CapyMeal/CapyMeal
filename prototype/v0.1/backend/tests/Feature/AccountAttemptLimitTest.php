<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * El límite por cuenta frena a quien prueba contraseñas o códigos contra una
 * misma cuenta aunque cambie de IP en cada intento (ver
 * App\Support\AccountAttemptLimiter). Cada intento acá sale de una IP
 * distinta, como haría alguien que inventa X-Forwarded-For.
 */
class AccountAttemptLimitTest extends TestCase
{
    use RefreshDatabase;

    private int $ip = 1;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function fromNewIp(): static
    {
        return $this->withHeader('X-Forwarded-For', '203.0.113.'.$this->ip++);
    }

    private function login(string $email, string $password)
    {
        return $this->fromNewIp()->postJson('/api/login', ['email' => $email, 'password' => $password]);
    }

    public function test_login_locks_the_account_after_five_failures_from_different_ips(): void
    {
        User::factory()->create(['email' => 'ana@example.com', 'password' => Hash::make('capymeal123')]);

        for ($i = 0; $i < 5; $i++) {
            $this->login('ana@example.com', 'incorrecta')->assertStatus(422);
        }

        // Bloqueada: ni siquiera la contraseña correcta entra hasta que pase.
        $this->login('ana@example.com', 'capymeal123')
            ->assertStatus(429)
            ->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_the_lock_does_not_reveal_whether_the_email_exists(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->login('no-existe@example.com', 'incorrecta')->assertStatus(422);
        }

        $this->login('no-existe@example.com', 'incorrecta')->assertStatus(429);
    }

    public function test_a_successful_login_resets_the_count(): void
    {
        User::factory()->create(['email' => 'ana@example.com', 'password' => Hash::make('capymeal123')]);

        for ($i = 0; $i < 4; $i++) {
            $this->login('ana@example.com', 'incorrecta')->assertStatus(422);
        }
        $this->login('ana@example.com', 'capymeal123')->assertOk();

        $this->login('ana@example.com', 'incorrecta')->assertStatus(422);
    }

    public function test_locking_one_account_does_not_affect_another(): void
    {
        User::factory()->create(['email' => 'leo@example.com', 'password' => Hash::make('capymeal123')]);

        for ($i = 0; $i < 5; $i++) {
            $this->login('ana@example.com', 'incorrecta');
        }

        $this->login('leo@example.com', 'capymeal123')->assertOk();
    }

    public function test_two_factor_code_locks_after_five_wrong_codes_even_with_new_challenges(): void
    {
        $secret = (new Google2FA)->generateSecretKey();
        User::factory()->create([
            'email' => 'ana@example.com',
            'password' => Hash::make('capymeal123'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $challenge = fn () => $this->login('ana@example.com', 'capymeal123')->json('challenge');

        for ($i = 0; $i < 5; $i++) {
            $this->fromNewIp()->postJson('/api/login/two-factor', ['challenge' => $challenge(), 'code' => '000000'])
                ->assertStatus(422);
        }

        // Con un código válido igual queda bloqueado hasta que pase el tiempo.
        $this->fromNewIp()->postJson('/api/login/two-factor', [
            'challenge' => $challenge(),
            'code' => (new Google2FA)->getCurrentOtp($secret),
        ])->assertStatus(429)->assertJsonValidationErrors('code');
        $this->assertGuest();
    }
}
