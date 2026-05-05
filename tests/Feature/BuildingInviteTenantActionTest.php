<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Filament\Resources\Buildings\Pages\ViewBuilding;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Invite;
use App\Models\User;
use App\Notifications\TenantInviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class BuildingInviteTenantActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_building_view_invite_tenant_action_creates_an_invite(): void
    {
        Notification::fake();

        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create([
            'building_id' => $building->getKey(),
            'number' => '12',
        ]);
        $manager = User::factory()->create();

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        $this->actingAs($manager);

        Livewire::test(ViewBuilding::class, ['record' => $building->getRouteKey()])
            ->callAction('inviteTenant', data: [
                'email' => 'tenant@example.com',
                'apartment_id' => $apartment->getKey(),
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $invite = Invite::query()
            ->where('building_id', $building->getKey())
            ->where('apartment_id', $apartment->getKey())
            ->where('email', 'tenant@example.com')
            ->first();

        $this->assertNotNull($invite);

        Notification::assertSentOnDemand(TenantInviteNotification::class, function (TenantInviteNotification $notification, array $channels, object $notifiable): bool {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === 'tenant@example.com';
        });
    }

    public function test_super_admin_can_invite_building_admin_from_building_view(): void
    {
        Notification::fake();

        $building = Building::factory()->create();
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->actingAs($superAdmin);

        Livewire::test(ViewBuilding::class, ['record' => $building->getRouteKey()])
            ->callAction('inviteAdmin', data: [
                'email' => 'manager@example.com',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $invite = Invite::query()
            ->where('building_id', $building->getKey())
            ->whereNull('apartment_id')
            ->where('email', 'manager@example.com')
            ->where('role', BuildingRole::PropertyManager->value)
            ->first();

        $this->assertNotNull($invite);

        Notification::assertSentOnDemand(TenantInviteNotification::class, function (TenantInviteNotification $notification, array $channels, object $notifiable): bool {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === 'manager@example.com';
        });
    }
}