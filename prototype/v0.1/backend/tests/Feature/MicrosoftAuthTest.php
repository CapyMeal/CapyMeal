<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

// Sólo lo específico de Microsoft vive acá -- el comportamiento genérico de
// exchange()/login()/destroy() contra una cuenta social-only está en
// SocialAuthExchangeTest, parametrizado sobre Google y Microsoft.
class MicrosoftAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_new_user_is_created_via_microsoft(): void
    {
        Socialite::fake('microsoft', SocialiteUser::fake([
            'id' => 'm-1',
            'name' => 'Mercedes',
            'email' => 'mercedes@example.com',
        ]));

        $this->get('/api/auth/microsoft/callback')->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'mercedes@example.com',
            'microsoft_id' => 'm-1',
            'password' => null,
        ]);

        $user = User::where('email', 'mercedes@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_existing_microsoft_user_logs_in_again_without_duplicating(): void
    {
        $user = User::factory()->create(['microsoft_id' => 'm-1', 'password' => null]);

        Socialite::fake('microsoft', SocialiteUser::fake([
            'id' => 'm-1',
            'name' => $user->name,
            'email' => $user->email,
        ]));

        $this->get('/api/auth/microsoft/callback');

        $this->assertSame(1, User::where('microsoft_id', 'm-1')->count());
    }

    public function test_microsoft_login_auto_links_existing_password_account(): void
    {
        $user = User::factory()->create([
            'email' => 'mercedes@example.com',
            'password' => Hash::make('capymeal123'),
            'microsoft_id' => null,
        ]);

        Socialite::fake('microsoft', SocialiteUser::fake([
            'id' => 'm-1',
            'name' => $user->name,
            'email' => 'mercedes@example.com',
        ]));

        $this->get('/api/auth/microsoft/callback');

        $user->refresh();
        $this->assertSame('m-1', $user->microsoft_id);
        $this->assertNotNull($user->password);
    }

    // Este es el test que verifica en código el hallazgo de
    // MicrosoftAuthController::extractEmail(): Socialite mapea getEmail()
    // a "userPrincipalName" (que en cuentas de trabajo/escuela puede ser
    // sólo un nombre de login, no un buzón verificado), así que el
    // auto-link tiene que usar "mail" (el buzón real de Graph) cuando está
    // presente, no el UPN.
    public function test_microsoft_login_auto_links_using_mail_not_user_principal_name(): void
    {
        $user = User::factory()->create([
            'email' => 'mercedes@example.com',
            'password' => Hash::make('capymeal123'),
            'microsoft_id' => null,
        ]);

        Socialite::fake('microsoft', SocialiteUser::fake([
            'id' => 'm-1',
            'name' => $user->name,
            'email' => 'nombre-de-login@empresa.example.com',
            'mail' => 'mercedes@example.com',
        ]));

        $this->get('/api/auth/microsoft/callback');

        $user->refresh();
        $this->assertSame('m-1', $user->microsoft_id);

        // Si extractEmail() hubiera usado el UPN en vez de "mail", esto
        // habría creado una segunda cuenta en vez de auto-vincular la
        // existente.
        $this->assertSame(1, User::count());
    }
}
