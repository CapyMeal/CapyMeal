<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

// Sólo lo específico de Google vive acá -- el comportamiento genérico de
// exchange()/login()/destroy() contra una cuenta social-only (que no
// depende de qué proveedor la creó) está en SocialAuthExchangeTest, ya
// parametrizado sobre Google y Microsoft.
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
}
