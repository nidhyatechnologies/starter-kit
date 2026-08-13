<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.manage',
            'permissions.manage',
            'audit.view',
        ])->map(fn (string $permission) => Permission::findOrCreate($permission, 'web'));

        $superAdmin = Role::findOrCreate('Super Admin', 'web');
        $superAdmin->syncPermissions($permissions);

        Permission::query()->where('name', 'manage access')->delete();

    }
}
