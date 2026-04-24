<?php

declare(strict_types=1);

use App\Enums\BuildingRole;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        setPermissionsTeamId(null);

        Role::findOrCreate('super_admin', 'web');
        $adminRole = Role::findOrCreate('admin', 'web');
        $tenantRole = Role::findOrCreate('tenant', 'web');

        $modelType = 'App\\Models\\User';

        $buildingAssignments = DB::table('building_user')->select(['building_id', 'user_id', 'role'])->get();

        foreach ($buildingAssignments as $assignment) {
            $roleId = match ($assignment->role) {
                BuildingRole::PropertyManager->value => $adminRole->getKey(),
                BuildingRole::Tenant->value => $tenantRole->getKey(),
                default => null,
            };

            if ($roleId === null) {
                continue;
            }

            DB::table('model_has_roles')->updateOrInsert([
                'building_id' => $assignment->building_id,
                'model_id' => $assignment->user_id,
                'model_type' => $modelType,
                'role_id' => $roleId,
            ], []);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('model_has_roles')) {
            return;
        }

        DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->delete();
    }
};