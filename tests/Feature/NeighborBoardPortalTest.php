<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Enums\NeighborBoardCategory;
use App\Enums\NeighborBoardPostStatus;
use App\Models\Building;
use App\Models\NeighborBoardPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeighborBoardPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_create_neighbor_board_post(): void
    {
        $tenant = User::factory()->create();
        $building = Building::factory()->create();
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->post(route('portal.neighbor-board.store'), [
                'building_id' => $building->getKey(),
                'category' => NeighborBoardCategory::Question->value,
                'title' => 'Da li neko ima višak sijalica?',
                'description' => 'Treba mi za hodnik, kupiću odmah.',
                'notify_residents' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('neighbor_board_posts', [
            'building_id' => $building->getKey(),
            'author_id' => $tenant->getKey(),
            'title' => 'Da li neko ima višak sijalica?',
            'status' => NeighborBoardPostStatus::Active->value,
        ]);
    }

    public function test_tenant_can_mark_own_post_as_resolved(): void
    {
        $tenant = User::factory()->create();
        $building = Building::factory()->create();
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        $post = NeighborBoardPost::factory()->create([
            'building_id' => $building->getKey(),
            'author_id' => $tenant->getKey(),
            'status' => NeighborBoardPostStatus::Active,
        ]);

        $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->post(route('portal.neighbor-board.resolve', $post))
            ->assertRedirect(route('portal.neighbor-board.show', $post));

        $this->assertDatabaseHas('neighbor_board_posts', [
            'id' => $post->getKey(),
            'status' => NeighborBoardPostStatus::Resolved->value,
        ]);
    }

    public function test_manager_can_lock_comments_and_tenants_can_no_longer_comment(): void
    {
        $manager = User::factory()->create();
        $tenant = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $building->users()->attach($tenant, ['role' => BuildingRole::Tenant->value]);

        $post = NeighborBoardPost::factory()->create([
            'building_id' => $building->getKey(),
            'author_id' => $tenant->getKey(),
            'comments_locked' => false,
        ]);

        $this->actingAs($manager)
            ->withSession(['current_building_id' => $building->getKey()])
            ->post(route('portal.neighbor-board.lock-comments', $post))
            ->assertRedirect(route('portal.neighbor-board.show', $post));

        $this->actingAs($tenant)
            ->withSession(['current_building_id' => $building->getKey()])
            ->post(route('portal.neighbor-board.comments.store', $post), [
                'building_id' => $building->getKey(),
                'body' => 'Novi komentar',
            ])
            ->assertForbidden();
    }

    public function test_posts_are_isolated_by_building_context(): void
    {
        $tenantA = User::factory()->create();
        $tenantB = User::factory()->create();
        $buildingA = Building::factory()->create();
        $buildingB = Building::factory()->create();

        $buildingA->users()->attach($tenantA, ['role' => BuildingRole::Tenant->value]);
        $buildingB->users()->attach($tenantB, ['role' => BuildingRole::Tenant->value]);

        $post = NeighborBoardPost::factory()->create([
            'building_id' => $buildingA->getKey(),
            'author_id' => $tenantA->getKey(),
        ]);

        $this->actingAs($tenantB)
            ->withSession(['current_building_id' => $buildingB->getKey()])
            ->get(route('portal.neighbor-board.show', $post))
            ->assertNotFound();
    }
}
