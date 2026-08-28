<?php

namespace Tests\Feature;

use App\Models\MealEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MealEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Las rutas de este grupo tienen throttle:60,1. El cache "array" de
        // testing vive durante todo el proceso del suite, así que sin esto
        // los intentos se acumulan entre tests y terminan en 429 (ver
        // AuthTest / MealEntryPdfExportTest).
        Cache::flush();
    }

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('capymeal')->plainTextToken];
    }

    public function test_user_can_list_own_entries_ordered_by_date_desc(): void
    {
        $user = User::factory()->create();
        MealEntry::create(['user_id' => $user->id, 'date' => '2026-08-01', 'breakfast' => 'Café']);
        MealEntry::create(['user_id' => $user->id, 'date' => '2026-08-10', 'breakfast' => 'Té']);

        $response = $this->withHeaders($this->authHeader($user))->getJson('/api/meal-entries');

        $response->assertOk();
        $response->assertJsonCount(2);
        $this->assertSame('2026-08-10', $response->json('0.date'));
    }

    public function test_user_can_filter_own_entries_by_date_range(): void
    {
        $user = User::factory()->create();
        MealEntry::create(['user_id' => $user->id, 'date' => '2026-08-01', 'breakfast' => 'Café']);
        MealEntry::create(['user_id' => $user->id, 'date' => '2026-08-10', 'breakfast' => 'Té']);
        MealEntry::create(['user_id' => $user->id, 'date' => '2026-08-20', 'breakfast' => 'Mate']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/meal-entries?from=2026-08-05&to=2026-08-15');

        $response->assertOk();
        $response->assertJsonCount(1);
        $this->assertSame('2026-08-10', $response->json('0.date'));
    }

    public function test_meal_entries_index_rejects_invalid_date_range(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/meal-entries?from=2026-08-20&to=2026-08-01');

        $response->assertStatus(422);
    }

    public function test_user_cannot_see_other_users_entries(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        MealEntry::create(['user_id' => $owner->id, 'date' => '2026-08-01', 'breakfast' => 'Café']);

        $response = $this->withHeaders($this->authHeader($other))->getJson('/api/meal-entries');

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    public function test_user_can_create_entry(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))->postJson('/api/meal-entries', [
            'date' => '2026-08-20',
            'breakfast' => 'Café con leche',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('meal_entries', [
            'user_id' => $user->id,
            'date' => '2026-08-20',
            'breakfast' => 'Café con leche',
        ]);
    }

    public function test_cannot_create_duplicate_entry_for_same_date(): void
    {
        $user = User::factory()->create();
        MealEntry::create(['user_id' => $user->id, 'date' => '2026-08-20', 'breakfast' => 'Café']);

        $response = $this->withHeaders($this->authHeader($user))->postJson('/api/meal-entries', [
            'date' => '2026-08-20',
            'breakfast' => 'Otra cosa',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, MealEntry::where('user_id', $user->id)->where('date', '2026-08-20')->count());
    }

    public function test_cannot_create_entry_with_all_fields_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))->postJson('/api/meal-entries', [
            'date' => '2026-08-20',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('meal_entries', ['user_id' => $user->id, 'date' => '2026-08-20']);
    }

    public function test_cannot_create_entry_with_field_longer_than_2000_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))->postJson('/api/meal-entries', [
            'date' => '2026-08-20',
            'breakfast' => str_repeat('a', 2001),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('breakfast');
        $this->assertDatabaseMissing('meal_entries', ['user_id' => $user->id, 'date' => '2026-08-20']);
    }

    public function test_can_create_entry_with_field_exactly_2000_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))->postJson('/api/meal-entries', [
            'date' => '2026-08-20',
            'breakfast' => str_repeat('a', 2000),
        ]);

        $response->assertCreated();
    }

    public function test_cannot_update_entry_with_field_longer_than_2000_characters(): void
    {
        $user = User::factory()->create();
        MealEntry::create(['user_id' => $user->id, 'date' => '2026-08-20', 'lunch' => 'Milanesa']);

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson('/api/meal-entries/2026-08-20', ['lunch' => str_repeat('a', 2001)]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('lunch');
        $this->assertDatabaseHas('meal_entries', ['user_id' => $user->id, 'date' => '2026-08-20', 'lunch' => 'Milanesa']);
    }

    public function test_user_can_view_single_entry_by_date(): void
    {
        $user = User::factory()->create();
        MealEntry::create(['user_id' => $user->id, 'date' => '2026-08-20', 'lunch' => 'Milanesa']);

        $response = $this->withHeaders($this->authHeader($user))->getJson('/api/meal-entries/2026-08-20');

        $response->assertOk();
        $response->assertJsonPath('lunch', 'Milanesa');
    }

    public function test_user_cannot_view_other_users_entry_by_date(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        MealEntry::create(['user_id' => $owner->id, 'date' => '2026-08-20', 'lunch' => 'Milanesa']);

        $response = $this->withHeaders($this->authHeader($other))
            ->getJson('/api/meal-entries/2026-08-20');

        // No se usa $response->json(): Laravel trata un body JSON `null`
        // literal como "decodificacion fallida" y hace fallar el test aunque
        // la respuesta sea perfectamente valida (ver TestResponse::decodeResponseJson).
        $response->assertOk();
        $response->assertContent('null');
    }

    public function test_viewing_a_date_with_no_entry_returns_null_not_404(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/meal-entries/2026-08-20');

        // No se usa $response->json(): Laravel trata un body JSON `null`
        // literal como "decodificacion fallida" y hace fallar el test aunque
        // la respuesta sea perfectamente valida (ver TestResponse::decodeResponseJson).
        $response->assertOk();
        $response->assertContent('null');
    }

    public function test_user_can_update_entry(): void
    {
        $user = User::factory()->create();
        MealEntry::create(['user_id' => $user->id, 'date' => '2026-08-20', 'lunch' => 'Milanesa']);

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson('/api/meal-entries/2026-08-20', ['lunch' => 'Ensalada']);

        $response->assertOk();
        $this->assertDatabaseHas('meal_entries', [
            'user_id' => $user->id,
            'date' => '2026-08-20',
            'lunch' => 'Ensalada',
        ]);
    }

    public function test_cannot_update_entry_to_all_empty_fields(): void
    {
        $user = User::factory()->create();
        MealEntry::create(['user_id' => $user->id, 'date' => '2026-08-20', 'lunch' => 'Milanesa']);

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson('/api/meal-entries/2026-08-20', ['lunch' => '']);

        $response->assertStatus(422);
        $this->assertDatabaseHas('meal_entries', [
            'user_id' => $user->id,
            'date' => '2026-08-20',
            'lunch' => 'Milanesa',
        ]);
    }

    public function test_user_can_delete_entry(): void
    {
        $user = User::factory()->create();
        MealEntry::create(['user_id' => $user->id, 'date' => '2026-08-20', 'lunch' => 'Milanesa']);

        $this->withHeaders($this->authHeader($user))
            ->deleteJson('/api/meal-entries/2026-08-20')
            ->assertNoContent();

        $this->assertDatabaseMissing('meal_entries', ['user_id' => $user->id, 'date' => '2026-08-20']);
    }

    public function test_user_cannot_delete_other_users_entry(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        MealEntry::create(['user_id' => $owner->id, 'date' => '2026-08-20', 'lunch' => 'Milanesa']);

        $this->withHeaders($this->authHeader($other))
            ->deleteJson('/api/meal-entries/2026-08-20')
            ->assertStatus(404);

        $this->assertDatabaseHas('meal_entries', ['user_id' => $owner->id, 'date' => '2026-08-20']);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/meal-entries')->assertStatus(401);
    }

    public function test_meal_entries_are_rate_limited_after_60_requests_per_minute(): void
    {
        $user = User::factory()->create();
        $headers = $this->authHeader($user);

        for ($i = 0; $i < 60; $i++) {
            $this->withHeaders($headers)->getJson('/api/meal-entries')->assertOk();
        }

        $this->withHeaders($headers)->getJson('/api/meal-entries')->assertStatus(429);
    }
}
