<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_through_the_web_portal(): void
    {
        $user = User::factory()->create([
            'email' => 'manager@upravnik.test',
        ]);

        $this->post('/login', [
            'email' => 'manager@upravnik.test',
            'password' => 'password',
        ])->assertRedirect(route('portal.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_switch_active_building_in_the_portal(): void
    {
        $user = User::factory()->create();
        $buildingA = Building::factory()->create(['name' => 'Alpha Residence']);
        $buildingB = Building::factory()->create(['name' => 'Beta Residence']);

        $buildingA->users()->attach($user, ['role' => BuildingRole::Tenant->value]);
        $buildingB->users()->attach($user, ['role' => BuildingRole::Tenant->value]);

        $this->actingAs($user)
            ->withSession(['current_building_id' => $buildingA->getKey()])
            ->post(route('portal.buildings.switch', $buildingB))
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('current_building_id', $buildingB->getKey());
    }

    public function test_tenant_can_create_ticket_from_the_portal(): void
    {
        $tenant = User::factory()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);
        $apartment->tenants()->attach($tenant);

        $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->post(route('portal.tickets.store'), [
                'building_id' => $building->getKey(),
                'apartment_id' => $apartment->getKey(),
                'title' => 'Water leak in hallway',
                'description' => 'There is a visible leak near the second-floor staircase.',
                'priority' => 'high',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'reported_by' => $tenant->getKey(),
            'title' => 'Water leak in hallway',
        ]);
    }

    public function test_tenant_can_quick_report_a_ticket_with_description_only(): void
    {
        $tenant = User::factory()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);
        $apartment->tenants()->attach($tenant);

        $description = 'The stairwell light on the third floor has been flickering since this morning.';

        $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->post(route('portal.tickets.store'), [
                'building_id' => $building->getKey(),
                'apartment_id' => $apartment->getKey(),
                'description' => $description,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'reported_by' => $tenant->getKey(),
            'priority' => 'medium',
            'title' => $description,
        ]);
    }
}