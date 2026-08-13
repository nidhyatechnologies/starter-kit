<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoUserSeeder extends Seeder
{
    /**
     * Seed a local-only administrator account for trying the starter kit.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $administrator = User::updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin User',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $administrator->syncRoles([Role::findByName('Super Admin')]);
    }
}
