<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuspendedBuildingWriteBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_cannot_create_ticket_in_suspended_building(): void
    {
        [$tenant, $building, $apartment] = $this->tenantInBuilding(fn () => Building::factory()->suspended());

        Sanctum::actingAs($tenant);

        $this->postJson('/api/tickets', [
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'title' => 'Broken light',
            'description' => 'The hallway light is out.',
            'priority' => 'medium',
        ])->assertForbidden();

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_tenant_can_still_read_history_in_suspended_building(): void
    {
        [$tenant, $building] = $this->tenantInBuilding(fn () => Building::factory()->suspended());

        Sanctum::actingAs($tenant);

        $this->getJson('/api/tickets?building_id='.$building->getKey())
            ->assertOk();
    }

    public function test_tenant_can_create_ticket_during_trial(): void
    {
        [$tenant, $building, $apartment] = $this->tenantInBuilding(fn () => Building::factory()->trial());

        Sanctum::actingAs($tenant);

        $this->postJson('/api/tickets', [
            'building_id' => $building->getKey(),
            'apartment_id' => $apartment->getKey(),
            'title' => 'Broken light',
            'description' => 'The hallway light is out.',
            'priority' => 'medium',
        ])->assertCreated();
    }

    /**
     * @param callable():\Illuminate\Database\Eloquent\Factories\Factory $buildingFactory
     * @return array{0: User, 1: Building, 2: Apartment}
     */
    private function tenantInBuilding(callable $buildingFactory): array
    {
        $tenant = User::factory()->create();
        $building = $buildingFactory()->create();
        $apartment = Apartment::factory()->create(['building_id' => $building->getKey()]);

        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);
        $apartment->tenants()->attach($tenant);

        return [$tenant, $building, $apartment];
    }
}
