<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Enums\BuildingStatus;
use App\Models\Building;
use App\Models\Poll;
use App\Models\User;
use App\Notifications\BuildingSuspendedNotification;
use App\Notifications\TrialReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProcessBuildingTrialsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_notifies_managers_when_trial_is_expiring_and_dedupes(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $building = Building::factory()->trial(5)->create();
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        $this->artisan('buildings:process-trials')->assertSuccessful();

        Notification::assertSentTo($manager, TrialReminderNotification::class);
        $this->assertDatabaseHas('building_trial_reminders', [
            'building_id' => $building->getKey(),
            'milestone' => 5,
        ]);

        // Second run must not send the same milestone again.
        Notification::fake();
        $this->artisan('buildings:process-trials')->assertSuccessful();
        Notification::assertNothingSent();
    }

    public function test_it_suspends_expired_trials_and_notifies_managers(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $building = Building::factory()->trialExpired()->create();
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        $this->artisan('buildings:process-trials')->assertSuccessful();

        $building->refresh();
        $this->assertSame(BuildingStatus::Suspended, $building->status);
        $this->assertNotNull($building->suspended_at);
        Notification::assertSentTo($manager, BuildingSuspendedNotification::class);
        $this->assertDatabaseHas('building_audit_logs', [
            'building_id' => $building->getKey(),
            'action' => 'suspended',
        ]);
    }

    public function test_it_generates_the_satisfaction_survey_on_the_final_day(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-01-01 09:00:00');

        $manager = User::factory()->create();
        // Ends later today: 0 days remaining but not yet past -> not suspended.
        $building = Building::factory()->create([
            'status' => BuildingStatus::Trial,
            'trial_started_at' => Carbon::now()->subDays(30),
            'trial_ends_at' => Carbon::now()->addHours(4),
        ]);
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        $this->artisan('buildings:process-trials')->assertSuccessful();

        $this->assertDatabaseHas('polls', [
            'building_id' => $building->getKey(),
        ]);
        $poll = Poll::query()->where('building_id', $building->getKey())->firstOrFail();
        $this->assertSame(5, $poll->options()->count());
        $this->assertSame(BuildingStatus::Trial, $building->refresh()->status);

        Carbon::setTestNow();
    }
}
