<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SetLocaleFromHeaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // /register tiene throttle:6,1 -- mismo motivo que en AuthTest.
        Cache::flush();
    }

    public function test_x_locale_en_header_returns_validation_errors_in_english(): void
    {
        $response = $this->withHeader('X-Locale', 'en')
            ->postJson('/api/register', []);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.name.0', 'The name field is required.');
    }

    public function test_x_locale_es_header_returns_validation_errors_in_spanish(): void
    {
        $response = $this->withHeader('X-Locale', 'es')
            ->postJson('/api/register', []);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.name.0', 'El campo nombre es obligatorio.');
    }

    public function test_missing_x_locale_header_falls_back_to_the_configured_default_locale(): void
    {
        // config/app.php: APP_LOCALE=es en .env, así que sin header
        // debería comportarse igual que el test de arriba con X-Locale: es.
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.name.0', 'El campo nombre es obligatorio.');
    }

    public function test_invalid_x_locale_header_value_falls_back_to_the_configured_default_locale_without_erroring(): void
    {
        $response = $this->withHeader('X-Locale', 'fr')
            ->postJson('/api/register', []);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.name.0', 'El campo nombre es obligatorio.');
    }

    public function test_notification_mail_content_differs_by_locale(): void
    {
        $user = User::factory()->make(['email' => 'mercedes@example.com']);
        $notification = new ResetPasswordNotification('un-token-cualquiera');

        App::setLocale('es');
        $mailEs = $notification->toMail($user);

        App::setLocale('en');
        $mailEn = $notification->toMail($user);

        $this->assertSame('Recuperá tu contraseña de CapyMeal 🍂', $mailEs->subject);
        $this->assertSame('Reset your CapyMeal password 🍂', $mailEn->subject);
        $this->assertNotSame($mailEs->subject, $mailEn->subject);
        $this->assertNotSame($mailEs->greeting, $mailEn->greeting);
    }
}
