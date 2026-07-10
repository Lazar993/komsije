<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\BuildingStatus;
use App\Models\Building;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BuildingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_building_automatically_starts_a_30_day_trial(): void
    {
        Carbon::setTestNow('2026-01-01 10:00:00');

        $building = Building::factory()->create();

        $this->assertSame(BuildingStatus::Trial, $building->status);
        $this->assertTrue($building->isTrial());
        $this->assertNotNull($building->trial_started_at);
        $this->assertEqualsWithDelta(
            Carbon::now()->addDays(30)->timestamp,
            $building->trial_ends_at->timestamp,
            5,
        );

        Carbon::setTestNow();
    }

    public function test_days_remaining_and_expiry_helpers(): void
    {
        Carbon::setTestNow('2026-01-01 10:00:00');

        $building = Building::factory()->trial(10)->create();
        $this->assertSame(10, $building->daysRemaining());
        $this->assertFalse($building->isExpired());

        $expired = Building::factory()->trialExpired()->create();
        $this->assertTrue($expired->isExpired());
        $this->assertLessThanOrEqual(0, $expired->daysRemaining());

        Carbon::setTestNow();
    }

    public function test_trial_progress_is_bounded_between_0_and_100(): void
    {
        Carbon::setTestNow('2026-01-15 10:00:00');

        $building = Building::factory()->create([
            'trial_started_at' => Carbon::now()->subDays(15),
            'trial_ends_at' => Carbon::now()->addDays(15),
            'status' => BuildingStatus::Trial,
        ]);

        $this->assertEqualsWithDelta(50, $building->trialProgress(), 2);

        Carbon::setTestNow();
    }

    public function test_allows_writes_only_for_trial_and_active(): void
    {
        $this->assertTrue(Building::factory()->trial()->create()->allowsWrites());
        $this->assertTrue(Building::factory()->active()->create()->allowsWrites());
        $this->assertFalse(Building::factory()->suspended()->create()->allowsWrites());
        $this->assertFalse(Building::factory()->archived()->create()->allowsWrites());
    }
}
