<?php

namespace Tests\Feature;

use App\Models\MealEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_password_deletes_user_tokens_and_meal_entries(): void
    {
        $user = User::factory()->create(['password' => Hash::make('capymeal123')]);
        $token = $user->createToken('capymeal')->plainTextToken;

        MealEntry::create([
            'user_id' => $user->id,
            'date' => '2026-08-20',
            'breakfast' => 'Café con leche',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/me', ['password' => 'capymeal123']);

        $response->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('meal_entries', ['user_id' => $user->id]);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->count());
    }

    public function test_wrong_password_does_not_delete_anything(): void
    {
        $user = User::factory()->create(['password' => Hash::make('capymeal123')]);
        $token = $user->createToken('capymeal')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/me', ['password' => 'contraseña-incorrecta']);

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_token_stops_working_after_account_is_deleted(): void
    {
        $user = User::factory()->create(['password' => Hash::make('capymeal123')]);
        $token = $user->createToken('capymeal')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/me', ['password' => 'capymeal123'])
            ->assertNoContent();

        // El guard de auth cachea el usuario resuelto en la primera llamada
        // dentro del mismo método de test -- sin esto, la segunda request
        // reusaría esa cache en vez de volver a resolver el token (ya
        // borrado) contra la base.
        auth()->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertStatus(401);
    }
}
