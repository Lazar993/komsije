<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Enums\TicketStatus;
use App\Enums\TicketVisibility;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\PublicTicketCreatedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketStatusChangedNotification;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_create_ticket_and_manager_is_notified(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $tenant = User::factory()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);
        $apartment->tenants()->attach($tenant);

        Sanctum::actingAs($tenant);

        $this->postJson('/api/tickets', [
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'title' => 'Broken hallway light',
            'description' => 'The hallway light on the second floor is out.',
            'priority' => 'medium',
        ])->assertCreated();

        $this->assertDatabaseHas('tickets', [
            'building_id' => $building->getKey(),
            'title' => 'Broken hallway light',
        ]);

        Notification::assertSentTo($manager, TicketCreatedNotification::class);
    }

    public function test_public_ticket_notifies_all_building_tenants_except_reporter(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $reporter = User::factory()->create();
        $otherTenant = User::factory()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $building->users()->attach($reporter, ['role' => BuildingRole::Tenant->value]);
        $building->users()->attach($otherTenant, ['role' => BuildingRole::Tenant->value]);
        $apartment->tenants()->attach($reporter);

        Sanctum::actingAs($reporter);

        $this->postJson('/api/tickets', [
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'title' => 'Elevator is broken',
            'description' => 'The elevator has been stuck since the morning.',
            'priority' => 'high',
            'visibility' => 'public',
        ])->assertCreated();

        Notification::assertSentTo($otherTenant, PublicTicketCreatedNotification::class);
        Notification::assertNotSentTo($reporter, PublicTicketCreatedNotification::class);
        Notification::assertSentTo($manager, TicketCreatedNotification::class);
    }

    public function test_private_ticket_does_not_notify_other_tenants(): void
    {
        Notification::fake();

        $reporter = User::factory()->create();
        $otherTenant = User::factory()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($reporter, ['role' => BuildingRole::Tenant->value]);
        $building->users()->attach($otherTenant, ['role' => BuildingRole::Tenant->value]);
        $apartment->tenants()->attach($reporter);

        Sanctum::actingAs($reporter);

        $this->postJson('/api/tickets', [
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'title' => 'Leaking sink in my kitchen',
            'description' => 'Water is dripping under the kitchen sink.',
            'priority' => 'medium',
            'visibility' => 'private',
        ])->assertCreated();

        Notification::assertNotSentTo($otherTenant, PublicTicketCreatedNotification::class);
    }

    public function test_public_ticket_status_change_notifies_all_building_tenants(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $reporter = User::factory()->create();
        $otherTenant = User::factory()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $building->users()->attach($reporter, ['role' => BuildingRole::Tenant->value]);
        $building->users()->attach($otherTenant, ['role' => BuildingRole::Tenant->value]);
        $apartment->tenants()->attach($reporter);

        $ticket = Ticket::factory()->create([
            'apartment_id' => $apartment->getKey(),
            'assigned_to' => $manager->getKey(),
            'building_id' => $building->getKey(),
            'reported_by' => $reporter->getKey(),
            'status' => TicketStatus::New->value,
            'visibility' => TicketVisibility::Public,
        ]);

        $this->app->make(TicketService::class)->update($ticket->load('building'), $manager, [
            'status' => TicketStatus::InProgress->value,
        ]);

        Notification::assertSentTo($reporter, TicketStatusChangedNotification::class);
        Notification::assertSentTo($otherTenant, TicketStatusChangedNotification::class);
        Notification::assertNotSentTo($manager, TicketStatusChangedNotification::class);
    }

    public function test_private_ticket_status_change_does_not_notify_uninvolved_tenants(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $reporter = User::factory()->create();
        $otherTenant = User::factory()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $building->users()->attach($reporter, ['role' => BuildingRole::Tenant->value]);
        $building->users()->attach($otherTenant, ['role' => BuildingRole::Tenant->value]);
        $apartment->tenants()->attach($reporter);

        $ticket = Ticket::factory()->create([
            'apartment_id' => $apartment->getKey(),
            'assigned_to' => $manager->getKey(),
            'building_id' => $building->getKey(),
            'reported_by' => $reporter->getKey(),
            'status' => TicketStatus::New->value,
            'visibility' => TicketVisibility::Private,
        ]);

        $this->app->make(TicketService::class)->update($ticket->load('building'), $manager, [
            'status' => TicketStatus::InProgress->value,
        ]);

        Notification::assertSentTo($reporter, TicketStatusChangedNotification::class);
        Notification::assertNotSentTo($otherTenant, TicketStatusChangedNotification::class);
    }
}