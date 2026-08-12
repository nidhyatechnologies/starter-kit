<?php

namespace Database\Seeders;

use App\Models\User;
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

        $permission = Permission::findOrCreate('manage access', 'web');

        $superAdmin = Role::findOrCreate('Super Admin', 'web');
        $superAdmin->syncPermissions([$permission]);

        User::query()
            ->where('email', 'admin@example.com')
            ->first()
            ?->syncRoles([$superAdmin]);
    }
}
