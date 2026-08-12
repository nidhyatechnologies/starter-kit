<?php

use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('users without the access permission cannot visit role management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('access-control.index'))
        ->assertForbidden();

    $this->get(route('users.index'))
        ->assertForbidden();
});

test('an access manager can create roles, permissions, and assignments', function () {
    $manager = User::factory()->create();
    $manageAccess = Permission::findOrCreate('manage access');
    $managerRole = Role::findOrCreate('Access Manager');
    $managerRole->givePermissionTo($manageAccess);
    $manager->assignRole($managerRole);

    $this->actingAs($manager);

    Livewire::test('pages::access-control')
        ->set('permissionName', 'view reports')
        ->call('savePermission')
        ->assertHasNoErrors();

    $permission = Permission::findByName('view reports');

    Livewire::test('pages::access-control')
        ->set('roleName', 'Report Viewer')
        ->set('rolePermissionIds', [$permission->id])
        ->call('saveRole')
        ->assertHasNoErrors();

    $role = Role::findByName('Report Viewer');

    expect($role->hasPermissionTo($permission))->toBeTrue();

    $member = User::factory()->create();

    $this->get(route('users.index'))
        ->assertSuccessful()
        ->assertSee('All users');

    Livewire::test('pages::users')
        ->call('editUser', $member->id)
        ->set('roleIds', [$role->id])
        ->call('saveRoles')
        ->assertHasNoErrors();

    expect($member->fresh()->hasRole($role))->toBeTrue();
});
