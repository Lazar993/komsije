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

class BuildingTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_another_building_context(): void
    {
        $tenant = User::factory()->create();
        $buildingA = Building::factory()->create();
        $buildingB = Building::factory()->create();

        Apartment::factory()->create(['building_id' => $buildingB->getKey()]);
        $buildingA->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        Sanctum::actingAs($tenant);

        $this->getJson('/api/apartments?building_id='.$buildingB->getKey())
            ->assertForbidden();
    }
}