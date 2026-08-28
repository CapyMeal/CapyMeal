<?php

namespace Tests\Unit\Policies;

use App\Models\MealEntry;
use App\Models\User;
use App\Policies\MealEntryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealEntryPolicyTest extends TestCase
{
    use RefreshDatabase;

    private MealEntryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new MealEntryPolicy;
    }

    public function test_owner_can_view_update_and_delete_their_entry(): void
    {
        $owner = User::factory()->create();
        $entry = MealEntry::create(['user_id' => $owner->id, 'date' => '2026-08-20', 'breakfast' => 'Café']);

        $this->assertTrue($this->policy->view($owner, $entry));
        $this->assertTrue($this->policy->update($owner, $entry));
        $this->assertTrue($this->policy->delete($owner, $entry));
    }

    public function test_other_user_cannot_view_update_or_delete_the_entry(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $entry = MealEntry::create(['user_id' => $owner->id, 'date' => '2026-08-20', 'breakfast' => 'Café']);

        $this->assertFalse($this->policy->view($other, $entry));
        $this->assertFalse($this->policy->update($other, $entry));
        $this->assertFalse($this->policy->delete($other, $entry));
    }
}
