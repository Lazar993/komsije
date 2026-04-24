<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAccessScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_building_admin_sees_only_assigned_buildings(): void
    {
        $admin = User::factory()->create();
        $assignedBuilding = Building::factory()->create(['name' => 'Assigned']);
        $otherBuilding = Building::factory()->create(['name' => 'Other']);

        $assignedBuilding->users()->attach($admin, ['role' => BuildingRole::PropertyManager->value]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/buildings');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Assigned']);
        $response->assertJsonMissing(['name' => 'Other']);
    }

    public function test_building_admin_cannot_create_buildings(): void
    {
        $admin = User::factory()->create();
        $building = Building::factory()->create();
        $building->users()->attach($admin, ['role' => BuildingRole::PropertyManager->value]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/buildings', [
            'name' => 'Unauthorized Building',
            'address' => 'Nowhere 1',
        ])->assertForbidden();
    }
}