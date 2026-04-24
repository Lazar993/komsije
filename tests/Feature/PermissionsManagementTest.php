<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\Permissions\PermissionResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Building;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_resource_syncs_building_admin_membership_into_spatie_team_role(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $building = Building::factory()->create();
        $adminRoleId = Role::query()->where('name', 'admin')->whereNull('building_id')->value('id');

        UserResource::syncRelationships($user, [
            'manager_building_ids' => [$building->getKey()],
            'tenant_building_ids' => [],
            'apartment_ids' => [],
        ]);

        $this->assertDatabaseHas('building_user', [
            'building_id' => $building->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'property_manager',
        ]);

        $this->assertDatabaseHas('model_has_roles', [
            'building_id' => $building->getKey(),
            'model_id' => $user->getKey(),
            'model_type' => User::class,
            'role_id' => $adminRoleId,
        ]);

        $this->assertTrue($user->fresh()->isBuildingAdmin($building->getKey()));
    }

    public function test_user_resource_clears_removed_building_role_assignments(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $building = Building::factory()->create();
        $adminRoleId = Role::query()->where('name', 'admin')->whereNull('building_id')->value('id');

        UserResource::syncRelationships($user, [
            'manager_building_ids' => [$building->getKey()],
            'tenant_building_ids' => [],
            'apartment_ids' => [],
        ]);

        UserResource::syncRelationships($user->fresh(), [
            'manager_building_ids' => [],
            'tenant_building_ids' => [],
            'apartment_ids' => [],
        ]);

        $this->assertDatabaseMissing('building_user', [
            'building_id' => $building->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->assertDatabaseMissing('model_has_roles', [
            'building_id' => $building->getKey(),
            'model_id' => $user->getKey(),
            'model_type' => User::class,
            'role_id' => $adminRoleId,
        ]);
    }

    public function test_only_super_admin_can_access_role_and_permission_resources(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $superAdmin->syncGlobalRoles(['super_admin']);

        $regularUser = User::factory()->create();

        $this->actingAs($superAdmin);
        $this->assertTrue(RoleResource::canAccess());
        $this->assertTrue(PermissionResource::canAccess());

        $this->actingAs($regularUser);
        $this->assertFalse(RoleResource::canAccess());
        $this->assertFalse(PermissionResource::canAccess());
    }
}