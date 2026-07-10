<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Enums\BuildingStatus;
use App\Models\Building;
use App\Models\User;
use App\Notifications\BuildingActivatedNotification;
use App\Services\BuildingLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BuildingLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BuildingLifecycleService
    {
        return app(BuildingLifecycleService::class);
    }

    public function test_activation_sets_status_and_logs_audit_and_notifies(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $manager = User::factory()->create();
        $building = Building::factory()->suspended()->create();
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        $this->service()->activate($building, $superAdmin);

        $building->refresh();
        $this->assertSame(BuildingStatus::Active, $building->status);
        $this->assertNotNull($building->subscription_started_at);
        $this->assertNull($building->suspended_at);

        Notification::assertSentTo($manager, BuildingActivatedNotification::class);
        $this->assertDatabaseHas('building_audit_logs', [
            'building_id' => $building->getKey(),
            'actor_id' => $superAdmin->getKey(),
            'action' => 'activated',
        ]);
    }

    public function test_extend_trial_pushes_expiry_out_and_clears_reminders(): void
    {
        Carbon::setTestNow('2026-01-01 10:00:00');

        $building = Building::factory()->trial(3)->create();
        $building->trialReminders()->create(['milestone' => 7, 'sent_at' => now()]);

        $this->service()->extendTrial($building, 10);

        $building->refresh();
        $this->assertSame(13, $building->daysRemaining());
        $this->assertSame(0, $building->trialReminders()->count());
        $this->assertDatabaseHas('building_audit_logs', [
            'building_id' => $building->getKey(),
            'action' => 'trial_extended',
        ]);

        Carbon::setTestNow();
    }

    public function test_restart_trial_resets_the_clock(): void
    {
        Carbon::setTestNow('2026-01-01 10:00:00');

        $building = Building::factory()->trialExpired()->create();

        $this->service()->restartTrial($building, null, 30);

        $building->refresh();
        $this->assertSame(BuildingStatus::Trial, $building->status);
        $this->assertSame(30, $building->daysRemaining());

        Carbon::setTestNow();
    }

    public function test_archive_preserves_data_and_is_read_only(): void
    {
        $building = Building::factory()->active()->create();

        $this->service()->archive($building);

        $building->refresh();
        $this->assertSame(BuildingStatus::Archived, $building->status);
        $this->assertNotNull($building->archived_at);
        $this->assertFalse($building->allowsWrites());
    }

    public function test_manager_cannot_change_subscription_status(): void
    {
        $manager = User::factory()->create();
        $building = Building::factory()->trial()->create();
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        $this->assertFalse($manager->can('activate', $building));
        $this->assertFalse($manager->can('suspend', $building));
        $this->assertFalse($manager->can('archive', $building));
        $this->assertFalse($manager->can('manageTrial', $building));
    }

    public function test_super_admin_may_manage_subscription_status(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $building = Building::factory()->trial()->create();

        $this->assertTrue($superAdmin->can('activate', $building));
        $this->assertTrue($superAdmin->can('suspend', $building));
        $this->assertTrue($superAdmin->can('archive', $building));
        $this->assertTrue($superAdmin->can('manageTrial', $building));
    }
}
