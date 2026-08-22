<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // /forgot-password y /reset-password tienen throttle:6,1; el cache
        // "array" de testing persiste durante todo el proceso del suite.
        Cache::flush();
    }

    public function test_forgot_password_sends_reset_notification_for_existing_user(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'mercedes@example.com']);

        $response = $this->postJson('/api/forgot-password', ['email' => 'mercedes@example.com']);

        $response->assertOk();
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_returns_generic_message_for_nonexistent_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/forgot-password', ['email' => 'no-existe@example.com']);

        // Misma respuesta exista o no la cuenta, para no filtrar emails registrados.
        $response->assertOk();
        Notification::assertNothingSent();
    }

    public function test_reset_password_with_valid_token_updates_password_and_revokes_tokens(): void
    {
        $user = User::factory()->create(['password' => Hash::make('vieja-contraseña')]);
        $oldToken = $user->createToken('capymeal')->plainTextToken;
        $resetToken = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $resetToken,
            'email' => $user->email,
            'password' => 'nueva-contraseña-123',
            'password_confirmation' => 'nueva-contraseña-123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('nueva-contraseña-123', $user->fresh()->password));

        $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/me')
            ->assertStatus(401);
    }

    public function test_reset_password_with_invalid_token_fails(): void
    {
        $user = User::factory()->create(['password' => Hash::make('vieja-contraseña')]);

        $response = $this->postJson('/api/reset-password', [
            'token' => 'token-invalido',
            'email' => $user->email,
            'password' => 'nueva-contraseña-123',
            'password_confirmation' => 'nueva-contraseña-123',
        ]);

        $response->assertStatus(422);
        $this->assertTrue(Hash::check('vieja-contraseña', $user->fresh()->password));
    }
}
