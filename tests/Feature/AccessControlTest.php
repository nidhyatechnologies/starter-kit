<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AccountSetupInvitation;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('the base seeder creates authorization roles without demo credentials', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'admin@example.com')->exists())->toBeFalse();

    $superAdmin = Role::findByName('Super Admin');

    expect($superAdmin->hasPermissionTo('users.delete'))->toBeTrue()
        ->and($superAdmin->hasPermissionTo('roles.manage'))->toBeTrue();
});

test('the demo user is only created by its explicit development seeder', function () {
    $this->seed(DemoUserSeeder::class);

    $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();

    expect($administrator->hasVerifiedEmail())->toBeTrue()
        ->and($administrator->hasRole('Super Admin'))->toBeTrue()
        ->and($administrator->can('users.delete'))->toBeTrue();
});

test('users without the access permission cannot visit role management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('access-control.index'))
        ->assertForbidden();

    $this->get(route('users.index'))
        ->assertForbidden();
});

test('access permissions limit user and role management actions', function () {
    $manager = User::factory()->create();
    $permissions = collect([
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'roles.manage',
        'permissions.manage',
    ])->map(fn (string $permission): Permission => Permission::findOrCreate($permission));
    $managerRole = Role::findOrCreate('Access Manager');
    $managerRole->syncPermissions($permissions);
    $manager->assignRole($managerRole);

    $this->actingAs($manager);

    Livewire::test('pages::roles.index')
        ->set('permissionName', 'view reports')
        ->call('savePermission')
        ->assertHasNoErrors();

    $permission = Permission::findByName('users.view');

    Livewire::test('pages::roles.index')
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

    Livewire::test('pages::users.index')
        ->call('editUser', $member->id)
        ->set('roleIds', [$role->id])
        ->call('updateUser')
        ->assertHasNoErrors();

    expect($member->fresh()->hasRole($role))->toBeTrue();
});

test('role managers cannot delegate permissions they do not hold', function () {
    $manager = User::factory()->create();
    $rolePermission = Permission::findOrCreate('roles.manage');
    $restrictedPermission = Permission::findOrCreate('users.delete');
    $managerRole = Role::findOrCreate('Role Manager');
    $managerRole->syncPermissions([$rolePermission]);
    $manager->assignRole($managerRole);

    $this->actingAs($manager);

    Livewire::test('pages::roles.index')
        ->set('roleName', 'Limited role')
        ->set('rolePermissionIds', [$restrictedPermission->id])
        ->call('saveRole')
        ->assertHasErrors('rolePermissionIds');
});

test('a user manager can create, update, and delete users', function () {
    $manager = User::factory()->create();
    collect(['users.view', 'users.create', 'users.update', 'users.delete'])
        ->each(fn (string $permission): Permission => Permission::findOrCreate($permission));
    $managerRole = Role::findOrCreate('User Manager');
    $managerRole->givePermissionTo(['users.view', 'users.create', 'users.update', 'users.delete']);
    $manager->assignRole($managerRole);

    $this->actingAs($manager);
    Notification::fake();

    Livewire::test('pages::users.index')
        ->set('name', 'New Team Member')
        ->set('email', 'member@example.com')
        ->set('password', 'secure-password')
        ->set('passwordConfirmation', 'secure-password')
        ->set('mustResetPassword', true)
        ->set('sendInvitation', true)
        ->set('roleIds', [$managerRole->id])
        ->call('createUser')
        ->assertHasNoErrors()
        ->assertSee('New Team Member');

    $member = User::query()->where('email', 'member@example.com')->firstOrFail();

    expect($member->must_reset_password)->toBeTrue();
    Notification::assertSentTo($member, AccountSetupInvitation::class, function (AccountSetupInvitation $notification) use ($member): bool {
        $message = $notification->toMail($member);

        return $message->subject === 'Set up your '.config('app.name').' account'
            && $message->actionText === 'Set up your account'
            && str($message->actionUrl)->contains('setup=1')
            && str($message->actionUrl)->contains('/reset-password/');
    });

    Livewire::test('pages::users.index')
        ->call('editUser', $member->id)
        ->set('name', 'Updated Team Member')
        ->call('updateUser')
        ->assertHasNoErrors();

    expect($member->fresh()->name)->toBe('Updated Team Member');

    Livewire::test('pages::users.index')
        ->call('deleteUser', $member->id)
        ->assertHasNoErrors();

    $this->assertModelMissing($member);
});

test('the Super Admin role cannot be changed or removed', function () {
    $superAdmin = Role::findOrCreate('Super Admin');
    $user = User::factory()->create();
    $user->assignRole($superAdmin);

    $this->actingAs($user);

    Livewire::test('pages::roles.index')
        ->call('editRole', $superAdmin->id)
        ->assertHasErrors('roleName')
        ->call('deleteRole', $superAdmin->id)
        ->assertHasErrors('roleName');

    expect($superAdmin->fresh()->name)->toBe('Super Admin');
});

test('non super administrators cannot manage Super Admin accounts', function () {
    $manager = User::factory()->create();
    $managerRole = Role::findOrCreate('User Manager');
    $managerRole->syncPermissions(collect(['users.view', 'users.update', 'users.delete'])->map(
        fn (string $permission): Permission => Permission::findOrCreate($permission),
    ));
    $manager->assignRole($managerRole);

    $superAdminRole = Role::findOrCreate('Super Admin');
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole($superAdminRole);

    $this->actingAs($manager);

    Livewire::test('pages::users.index')
        ->call('editUser', $superAdmin->id)
        ->assertHasErrors('user')
        ->call('deleteUser', $superAdmin->id)
        ->assertHasErrors('user');

    $this->assertModelExists($superAdmin);
});

test('audit viewers can review recorded management events', function () {
    $viewer = User::factory()->create();
    $auditRole = Role::findOrCreate('Audit Viewer');
    $auditRole->givePermissionTo(Permission::findOrCreate('audit.view'));
    $viewer->assignRole($auditRole);

    $auditLog = AuditLog::factory()->create();

    $this->actingAs($viewer)
        ->get(route('audit.index'))
        ->assertSuccessful()
        ->assertSee(str($auditLog->event)->replace('.', ' ')->headline());
});
