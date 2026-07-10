<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingJoinRequestStatus;
use App\Enums\BuildingRole;
use App\Models\Building;
use App\Models\BuildingJoinRequest;
use App\Models\Invite;
use App\Models\User;
use App\Notifications\NewResidentJoinRequestNotification;
use App\Notifications\PendingResidentJoinRequestReminderNotification;
use App\Services\BuildingJoinRequestService;
use App\Services\BuildingOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class BuildingJoinOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_onboarding_token_renders_join_page(): void
    {
        $building = Building::factory()->create(['onboarding_token' => 'abc123token']);

        $response = $this->get(route('join.show', ['token' => 'abc123token']));

        $response->assertOk();
        $response->assertSee($building->name);
    }

    public function test_invalid_onboarding_token_renders_professional_404_page(): void
    {
        $this->get(route('join.show', ['token' => 'missing-token']))
            ->assertStatus(404)
            ->assertSee('QR link nije važeći');
    }

    public function test_join_request_submission_creates_pending_request_and_notifies_managers(): void
    {
        Notification::fake();

        $building = Building::factory()->create(['onboarding_token' => 'join-me']);
        $manager = User::factory()->create();
        $building->users()->attach($manager->getKey(), ['role' => BuildingRole::PropertyManager->value]);

        $response = $this->post(route('join.store', ['token' => 'join-me']), [
            'first_name' => 'Petar',
            'last_name' => 'Petrovic',
            'apartment_number' => '24',
            'email' => 'petar@example.com',
            'phone' => '060123123',
            'privacy_accepted' => '1',
            'company' => '',
        ]);

        $response->assertRedirect(route('join.show', ['token' => 'join-me']));

        $this->assertDatabaseHas('building_join_requests', [
            'building_id' => $building->getKey(),
            'email' => 'petar@example.com',
            'status' => BuildingJoinRequestStatus::Pending->value,
        ]);

        Notification::assertSentTo(
            $manager,
            NewResidentJoinRequestNotification::class,
            function (NewResidentJoinRequestNotification $notification, array $channels): bool {
                unset($notification);

                return in_array('database', $channels, true)
                    && ! in_array('mail', $channels, true);
            }
        );
    }

    public function test_duplicate_pending_or_approved_requests_are_blocked(): void
    {
        $building = Building::factory()->create(['onboarding_token' => 'dup-token']);

        BuildingJoinRequest::factory()->create([
            'building_id' => $building->getKey(),
            'email' => 'resident@example.com',
            'status' => BuildingJoinRequestStatus::Pending,
        ]);

        $response = $this->from(route('join.show', ['token' => 'dup-token']))
            ->post(route('join.store', ['token' => 'dup-token']), [
                'first_name' => 'Mina',
                'last_name' => 'M',
                'apartment_number' => '12',
                'email' => 'resident@example.com',
                'phone' => null,
                'privacy_accepted' => '1',
                'company' => '',
            ]);

        $response->assertRedirect(route('join.show', ['token' => 'dup-token']));
        $response->assertSessionHasErrors('email');
    }

    public function test_approval_reuses_existing_invite_flow(): void
    {
        Notification::fake();

        $building = Building::factory()->create();
        $manager = User::factory()->create();
        $building->users()->attach($manager->getKey(), ['role' => BuildingRole::PropertyManager->value]);

        $joinRequest = BuildingJoinRequest::factory()->create([
            'building_id' => $building->getKey(),
            'status' => BuildingJoinRequestStatus::Pending,
            'email' => 'newtenant@example.com',
            'apartment_number' => '33',
        ]);

        $invite = app(BuildingJoinRequestService::class)->approve($joinRequest, $manager);

        $this->assertInstanceOf(Invite::class, $invite);
        $this->assertSame('newtenant@example.com', $invite->email);
        $this->assertSame($building->getKey(), (int) $invite->building_id);

        $this->assertDatabaseHas('building_join_requests', [
            'id' => $joinRequest->getKey(),
            'status' => BuildingJoinRequestStatus::Approved->value,
            'approved_by' => $manager->getKey(),
        ]);
    }

    public function test_pending_requests_older_than_24h_send_manager_email_reminder(): void
    {
        Notification::fake();

        $building = Building::factory()->create();
        $manager = User::factory()->create();
        $building->users()->attach($manager->getKey(), ['role' => BuildingRole::PropertyManager->value]);

        BuildingJoinRequest::factory()->create([
            'building_id' => $building->getKey(),
            'status' => BuildingJoinRequestStatus::Pending,
            'created_at' => now()->subHours(25),
            'updated_at' => now()->subHours(25),
            'manager_reminded_at' => null,
        ]);

        Artisan::call('join-requests:send-reminders');

        Notification::assertSentTo($manager, PendingResidentJoinRequestReminderNotification::class);

        $this->assertDatabaseHas('building_join_requests', [
            'building_id' => $building->getKey(),
        ]);

        $this->assertNotNull(
            BuildingJoinRequest::query()->where('building_id', $building->getKey())->firstOrFail()->manager_reminded_at
        );
    }

    public function test_onboarding_service_generates_token_once_and_can_regenerate(): void
    {
        $building = Building::factory()->create(['onboarding_token' => null]);
        $service = app(BuildingOnboardingService::class);

        $first = $service->ensureToken($building);
        $second = $service->ensureToken($first);
        $firstToken = (string) $first->onboarding_token;

        $this->assertNotNull($first->onboarding_token);
        $this->assertSame($first->onboarding_token, $second->onboarding_token);

        $regenerated = $service->regenerateToken($second);

        $this->assertNotSame($firstToken, $regenerated->onboarding_token);
    }
}
