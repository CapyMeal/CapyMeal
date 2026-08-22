<?php

namespace Tests\Feature;

use App\Models\MealEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealEntryTest extends TestCase
{
    use RefreshDatabase;

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

        $this->withHeaders($this->authHeader($other))
            ->getJson('/api/meal-entries/2026-08-20')
            ->assertStatus(404);
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
}
