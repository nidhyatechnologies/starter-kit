<?php

use Illuminate\Support\Facades\Gate;
use App\Actions\RecordAuditEvent;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.app'), Title('Roles & permissions')] class extends Component {
    public ?int $editingRoleId = null;

    public string $roleName = '';

    /** @var array<int, int> */
    public array $rolePermissionIds = [];

    public ?int $editingPermissionId = null;

    public string $permissionName = '';

    public function mount(): void
    {
        abort_unless(Gate::any(['roles.manage', 'permissions.manage']), 403);
    }

    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::query()->with('permissions:id,name')->orderBy('name')->get();
    }

    #[Computed]
    public function permissions(): \Illuminate\Database\Eloquent\Collection
    {
        return Permission::query()->orderBy('name')->get();
    }

    public function editRole(int $roleId): void
    {
        Gate::authorize('roles.manage');

        $role = Role::query()->with('permissions:id')->findOrFail($roleId);

        if ($role->name === 'Super Admin') {
            $this->addError('roleName', 'The Super Admin role is a protected system role.');

            return;
        }

        $this->editingRoleId = $role->id;
        $this->roleName = $role->name;
        $this->rolePermissionIds = $role->permissions->pluck('id')->all();
        $this->resetValidation('roleName');
    }

    public function saveRole(): void
    {
        Gate::authorize('roles.manage');

        if ($this->editingRoleId !== null && Role::query()->findOrFail($this->editingRoleId)->name === 'Super Admin') {
            $this->addError('roleName', 'The Super Admin role is a protected system role.');

            return;
        }

        $this->validate([
            'roleName' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->ignore($this->editingRoleId),
            ],
            'rolePermissionIds' => ['array'],
            'rolePermissionIds.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $this->ensurePermissionsCanBeDelegated();

        $role = Role::query()->updateOrCreate(
            ['id' => $this->editingRoleId],
            ['name' => $this->roleName, 'guard_name' => 'web'],
        );

        $role->syncPermissions(
            Permission::query()->whereKey($this->rolePermissionIds)->get(),
        );

        app(RecordAuditEvent::class)->handle('role.saved', null, ['role_id' => $role->id]);

        $this->resetRoleForm();
    }

    public function deleteRole(int $roleId): void
    {
        Gate::authorize('roles.manage');

        $role = Role::query()->findOrFail($roleId);

        if ($role->name === 'Super Admin') {
            $this->addError('roleName', 'The Super Admin role cannot be deleted.');

            return;
        }

        $role->delete();
        app(RecordAuditEvent::class)->handle('role.deleted', null, ['role_id' => $roleId]);

        if ($this->editingRoleId === $roleId) {
            $this->resetRoleForm();
        }
    }

    public function editPermission(int $permissionId): void
    {
        Gate::authorize('permissions.manage');

        $permission = Permission::query()->findOrFail($permissionId);

        $this->editingPermissionId = $permission->id;
        $this->permissionName = $permission->name;
        $this->resetValidation('permissionName');
    }

    public function savePermission(): void
    {
        Gate::authorize('permissions.manage');

        if ($this->editingPermissionId !== null && $this->isSystemPermission(Permission::query()->findOrFail($this->editingPermissionId))) {
            $this->addError('permissionName', 'System permissions cannot be renamed.');

            return;
        }

        $this->validate([
            'permissionName' => [
                'required',
                'string',
                'max:100',
                Rule::unique('permissions', 'name')->ignore($this->editingPermissionId),
            ],
        ]);

        Permission::query()->updateOrCreate(
            ['id' => $this->editingPermissionId],
            ['name' => $this->permissionName, 'guard_name' => 'web'],
        );

        app(RecordAuditEvent::class)->handle('permission.saved', null, ['permission' => $this->permissionName]);

        $this->resetPermissionForm();
    }

    public function deletePermission(int $permissionId): void
    {
        Gate::authorize('permissions.manage');

        $permission = Permission::query()->findOrFail($permissionId);

        if ($this->isSystemPermission($permission)) {
            $this->addError('permissionName', 'System permissions cannot be deleted.');

            return;
        }

        $permission->delete();
        app(RecordAuditEvent::class)->handle('permission.deleted', null, ['permission' => $permission->name]);

        if ($this->editingPermissionId === $permissionId) {
            $this->resetPermissionForm();
        }
    }

    public function resetRoleForm(): void
    {
        $this->reset('editingRoleId', 'roleName', 'rolePermissionIds');
        $this->resetValidation('roleName');
    }

    public function resetPermissionForm(): void
    {
        $this->reset('editingPermissionId', 'permissionName');
        $this->resetValidation('permissionName');
    }

    private function isSystemPermission(Permission $permission): bool
    {
        return in_array($permission->name, [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.manage',
            'permissions.manage',
            'audit.view',
        ], true);
    }

    private function ensurePermissionsCanBeDelegated(): void
    {
        if (auth()->user()->isSuperAdmin()) {
            return;
        }

        $hasUndelegatedPermission = Permission::query()
            ->whereKey($this->rolePermissionIds)
            ->get()
            ->contains(fn (Permission $permission): bool => ! auth()->user()->can($permission->name));

        if ($hasUndelegatedPermission) {
            throw ValidationException::withMessages([
                'rolePermissionIds' => 'You may only assign permissions you currently hold.',
            ]);
        }
    }
};
?>

<div class="settings-page settings-page--wide">
    <header class="settings-page__header">
        <h1 class="settings-page__title">Roles & permissions</h1>
        <p class="settings-page__description">Create access levels and choose exactly what each role can do.</p>
    </header>

    <div class="row g-4 align-items-start">
        <div class="col-lg-7">
            <section class="card settings-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <h2 class="settings-card__title">Roles</h2>
                            <p class="settings-card__copy">Assign permissions to a role, then assign that role to users.</p>
                        </div>
                        @if ($editingRoleId)
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetRoleForm">Cancel</button>
                        @endif
                    </div>

                    @can('roles.manage')
                    <form wire:submit="saveRole">
                        <label for="role-name" class="form-label">{{ $editingRoleId ? 'Role name' : 'New role name' }}</label>
                        <input wire:model="roleName" id="role-name" type="text" class="form-control @error('roleName') is-invalid @enderror" placeholder="e.g. Content manager">
                        @error('roleName') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <fieldset class="mt-4">
                            <legend class="form-label mb-2">Permissions</legend>
                            <div class="settings-permission-list">
                                @forelse ($this->permissions as $permission)
                                    @if (auth()->user()->can($permission->name))
                                    <label class="settings-permission-option" wire:key="role-permission-{{ $permission->id }}">
                                        <input wire:model="rolePermissionIds" class="form-check-input" type="checkbox" value="{{ $permission->id }}">
                                        <span>{{ $permission->name }}</span>
                                    </label>
                                    @endif
                                @empty
                                    <p class="small text-secondary mb-0">Create permissions first, then add them to roles.</p>
                                @endforelse
                            </div>
                            @error('rolePermissionIds') <p class="small text-danger mt-2 mb-0">{{ $message }}</p> @enderror
                        </fieldset>

                        <button type="submit" class="btn btn-primary mt-4" wire:loading.attr="disabled" wire:target="saveRole">
                            {{ $editingRoleId ? 'Save role' : 'Create role' }}
                        </button>
                    </form>
                    @endcan

                    <div class="border-top mt-4 pt-4">
                        <h3 class="settings-card__title fs-6 mb-3">Existing roles</h3>
                        <div class="vstack gap-2">
                            @foreach ($this->roles as $role)
                                <div class="settings-list-row" wire:key="role-{{ $role->id }}">
                                    <div>
                                        <strong>{{ $role->name }}</strong>
                                        <small>{{ $role->permissions->count() }} permissions</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        @can('roles.manage')
                                        @if ($role->name !== 'Super Admin')
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="editRole({{ $role->id }})">Edit</button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" wire:click="deleteRole({{ $role->id }})" wire:confirm="Delete this role?">Delete</button>
                                        @endif
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="card settings-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <h2 class="settings-card__title">Permissions</h2>
                            <p class="settings-card__copy">Permissions describe individual actions your application can allow.</p>
                        </div>
                        @if ($editingPermissionId)
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetPermissionForm">Cancel</button>
                        @endif
                    </div>

                    @can('permissions.manage')
                    <form wire:submit="savePermission">
                        <label for="permission-name" class="form-label">{{ $editingPermissionId ? 'Permission name' : 'New permission name' }}</label>
                        <input wire:model="permissionName" id="permission-name" type="text" class="form-control @error('permissionName') is-invalid @enderror" placeholder="e.g. manage reports">
                        @error('permissionName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <button type="submit" class="btn btn-primary mt-3" wire:loading.attr="disabled" wire:target="savePermission">
                            {{ $editingPermissionId ? 'Save permission' : 'Create permission' }}
                        </button>
                    </form>
                    @endcan

                    <div class="border-top mt-4 pt-4">
                        <h3 class="settings-card__title fs-6 mb-3">Existing permissions</h3>
                        <div class="vstack gap-2">
                            @foreach ($this->permissions as $permission)
                                <div class="settings-list-row" wire:key="permission-{{ $permission->id }}">
                                    <strong>{{ $permission->name }}</strong>
                                    <div class="d-flex gap-2">
                                        @can('permissions.manage')
                                        @if (! in_array($permission->name, ['users.view', 'users.create', 'users.update', 'users.delete', 'roles.manage', 'permissions.manage'], true))
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="editPermission({{ $permission->id }})">Edit</button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" wire:click="deletePermission({{ $permission->id }})" wire:confirm="Delete this permission?">Delete</button>
                                        @endif
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
