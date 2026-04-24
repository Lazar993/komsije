<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        setPermissionsTeamId(null);

        $permissions = [
            'manage role definitions',
            'manage permission definitions',
            'manage users',
            'manage buildings',
            'manage apartments',
            'manage tickets',
            'manage announcements',
            'create tickets',
            'view announcements',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $admin = Role::findOrCreate('admin', 'web');
        $tenant = Role::findOrCreate('tenant', 'web');

        $superAdmin->syncPermissions(Permission::query()->pluck('name')->all());
        $admin->syncPermissions([
            'manage users',
            'manage buildings',
            'manage apartments',
            'manage tickets',
            'manage announcements',
            'create tickets',
            'view announcements',
        ]);
        $tenant->syncPermissions([
            'create tickets',
            'view announcements',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}