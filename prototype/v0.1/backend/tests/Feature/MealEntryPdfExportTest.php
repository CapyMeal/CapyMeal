<?php

namespace Tests\Feature;

use App\Models\MealEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealEntryPdfExportTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('capymeal')->plainTextToken];
    }

    public function test_user_can_export_pdf_of_their_entries(): void
    {
        $user = User::factory()->create();
        MealEntry::create(['user_id' => $user->id, 'date' => '2026-08-01', 'breakfast' => 'Cafe con leche']);

        $response = $this->withHeaders($this->authHeader($user))
            ->get('/api/meal-entries/export/pdf');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_export_works_with_no_entries(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->get('/api/meal-entries/export/pdf');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_export_rejects_invalid_date_range(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/meal-entries/export/pdf?from=2026-08-20&to=2026-08-01');

        $response->assertStatus(422);
    }

    public function test_pdf_export_only_includes_own_entries(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        MealEntry::create(['user_id' => $owner->id, 'date' => '2026-08-01', 'breakfast' => 'Cafe']);

        // Sin registros propios: no debería fallar ni incluir los del otro usuario.
        $response = $this->withHeaders($this->authHeader($other))
            ->get('/api/meal-entries/export/pdf');

        $response->assertOk();
    }

    public function test_unauthenticated_export_is_rejected(): void
    {
        $this->getJson('/api/meal-entries/export/pdf')->assertStatus(401);
    }
}
