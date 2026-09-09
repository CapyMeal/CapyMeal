<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // /register tiene throttle:6,1 y el endpoint de reenvío throttle:3,1
        // -- mismo motivo que en AuthTest: el cache "array" de testing vive
        // durante todo el proceso del suite.
        Cache::flush();
    }

    private function signedVerifyUrl(User $user, ?string $hash = null, $expiresAt = null): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            $expiresAt ?? now()->addMinutes(60),
            ['id' => $user->id, 'hash' => $hash ?? sha1($user->email)]
        );
    }

    public function test_registering_with_password_sends_a_verification_email(): void
    {
        Notification::fake();

        $this->postJson('/api/register', [
            'name' => 'Mercedes',
            'email' => 'mercedes@example.com',
            'password' => 'capymeal123',
            'password_confirmation' => 'capymeal123',
        ])->assertCreated();

        $user = User::where('email', 'mercedes@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_me_exposes_email_verified_flag(): void
    {
        $unverified = User::factory()->unverified()->create();
        $token = $unverified->createToken('capymeal')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('email_verified', false);

        // Sin esto, la segunda request reusaría el guard ya resuelto en la
        // primera llamada de este mismo método en vez de validar de nuevo
        // contra el nuevo token -- mismo gotcha que en AuthTest.
        auth()->forgetGuards();

        $verified = User::factory()->create();
        $token = $verified->createToken('capymeal')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('email_verified', true);
    }

    public function test_visiting_a_valid_signed_link_marks_the_email_verified_and_redirects(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->get($this->signedVerifyUrl($user));

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $response->assertRedirect("{$frontendUrl}/email-verificado?status=ok");
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_visiting_the_link_twice_is_idempotent(): void
    {
        $user = User::factory()->unverified()->create();
        $url = $this->signedVerifyUrl($user);

        $this->get($url);
        $firstVerifiedAt = $user->fresh()->email_verified_at;

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $this->get($url)->assertRedirect("{$frontendUrl}/email-verificado?status=already");

        $this->assertEquals($firstVerifiedAt, $user->fresh()->email_verified_at);
    }

    public function test_a_link_whose_hash_no_longer_matches_the_users_email_is_rejected(): void
    {
        // Simula que el usuario cambió de email después de que se generó el
        // link: la firma de la URL sigue siendo válida (no se tocó ningún
        // parámetro), pero el hash ya no corresponde al email actual --
        // mismo chequeo que hace el EmailVerificationRequest de Laravel
        // por defecto.
        $user = User::factory()->unverified()->create(['email' => 'viejo@example.com']);
        $url = $this->signedVerifyUrl($user);

        $user->forceFill(['email' => 'nuevo@example.com'])->save();

        $this->get($url)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_an_expired_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $url = $this->signedVerifyUrl($user, expiresAt: now()->subMinute());

        $this->get($url)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verifying_a_nonexistent_user_returns_not_found(): void
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => 999999, 'hash' => sha1('no-existe@example.com')]
        );

        $this->get($url)->assertNotFound();
    }

    public function test_resend_requires_authentication(): void
    {
        $this->postJson('/api/email/verification-notification')->assertStatus(401);
    }

    public function test_resend_sends_a_new_notification_while_unverified(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $token = $user->createToken('capymeal')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/email/verification-notification')
            ->assertOk();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_resend_does_nothing_once_already_verified(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $token = $user->createToken('capymeal')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('message', 'Tu email ya está verificado.');

        Notification::assertNothingSent();
    }

    public function test_resend_is_rate_limited(): void
    {
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('capymeal')->plainTextToken;

        for ($i = 0; $i < 3; $i++) {
            $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/email/verification-notification')
                ->assertOk();
        }

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/email/verification-notification')
            ->assertStatus(429);
    }
}
