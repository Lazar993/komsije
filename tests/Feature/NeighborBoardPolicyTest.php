<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Enums\NeighborBoardPostStatus;
use App\Models\Building;
use App\Models\NeighborBoardPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeighborBoardPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_update_own_non_archived_post(): void
    {
        $tenant = User::factory()->create();
        $building = Building::factory()->create();
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        $post = NeighborBoardPost::factory()->create([
            'building_id' => $building->getKey(),
            'author_id' => $tenant->getKey(),
            'status' => NeighborBoardPostStatus::Active,
        ]);

        $this->assertTrue($tenant->can('update', $post));
    }

    public function test_tenant_cannot_update_other_tenant_post(): void
    {
        $author = User::factory()->create();
        $otherTenant = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($author, ['role' => BuildingRole::Tenant->value]);
        $building->users()->attach($otherTenant, ['role' => BuildingRole::Tenant->value]);

        $post = NeighborBoardPost::factory()->create([
            'building_id' => $building->getKey(),
            'author_id' => $author->getKey(),
        ]);

        $this->assertFalse($otherTenant->can('update', $post));
    }

    public function test_manager_can_archive_and_pin_building_posts(): void
    {
        $manager = User::factory()->create();
        $tenant = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        $post = NeighborBoardPost::factory()->create([
            'building_id' => $building->getKey(),
            'author_id' => $tenant->getKey(),
        ]);

        $this->assertTrue($manager->can('archive', $post));
        $this->assertTrue($manager->can('pin', $post));
    }
}
