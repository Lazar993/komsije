<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
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
}