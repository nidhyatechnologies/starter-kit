<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.app'), Title('Users')] class extends Component {
    public ?int $editingUserId = null;

    /** @var array<int, int> */
    public array $roleIds = [];

    public function mount(): void
    {
        Gate::authorize('manage access');
    }

    #[Computed]
    public function users(): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()
            ->with('roles:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'email_verified_at']);
    }

    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::query()->orderBy('name')->get(['id', 'name']);
    }

    public function editUser(int $userId): void
    {
        Gate::authorize('manage access');

        $user = User::query()->with('roles:id')->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->roleIds = $user->roles->pluck('id')->all();
        $this->resetValidation('roleIds');
    }

    public function saveRoles(): void
    {
        Gate::authorize('manage access');

        $this->validate([
            'editingUserId' => ['required', 'integer', Rule::exists('users', 'id')],
            'roleIds' => ['array'],
            'roleIds.*' => ['integer', Rule::exists('roles', 'id')],
        ]);

        $user = User::query()->findOrFail($this->editingUserId);
        $user->syncRoles(Role::query()->whereKey($this->roleIds)->get());

        $this->resetUserForm();
    }

    public function resetUserForm(): void
    {
        $this->reset('editingUserId', 'roleIds');
        $this->resetValidation('roleIds');
    }
};
?>

<div class="settings-page settings-page--wide">
    <header class="settings-page__header">
        <h1 class="settings-page__title">Users</h1>
        <p class="settings-page__description">Review user accounts and assign the roles that control their access.</p>
    </header>

    <div class="row g-4 align-items-start">
        <div class="col-lg-7">
            <section class="card settings-card">
                <div class="card-body p-0">
                    <div class="settings-table-header">
                        <h2 class="settings-card__title">All users</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">User</th>
                                    <th scope="col">Roles</th>
                                    <th scope="col" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->users as $user)
                                    <tr wire:key="user-{{ $user->id }}">
                                        <td>
                                            <strong class="d-block">{{ $user->name }}</strong>
                                            <small class="text-secondary">{{ $user->email }}</small>
                                        </td>
                                        <td>
                                            @forelse ($user->roles as $role)
                                                <span class="badge text-bg-light border me-1">{{ $role->name }}</span>
                                            @empty
                                                <span class="text-secondary small">No role</span>
                                            @endforelse
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="editUser({{ $user->id }})">Manage</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-secondary text-center py-4">No users found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="card settings-card">
                <div class="card-body">
                    <h2 class="settings-card__title">Manage roles</h2>
                    <p class="settings-card__copy">Select a user from the list to change their assigned roles.</p>

                    @if ($editingUserId)
                        @php($editingUser = $this->users->firstWhere('id', $editingUserId))
                        <form wire:submit="saveRoles">
                            <p class="small fw-semibold mb-3">{{ $editingUser?->name }}</p>
                            <div class="settings-permission-list settings-permission-list--single">
                                @foreach ($this->roles as $role)
                                    <label class="settings-permission-option" wire:key="user-role-{{ $role->id }}">
                                        <input wire:model="roleIds" class="form-check-input" type="checkbox" value="{{ $role->id }}">
                                        <span>{{ $role->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('roleIds') <p class="small text-danger mt-2 mb-0">{{ $message }}</p> @enderror
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveRoles">Save roles</button>
                                <button type="button" class="btn btn-outline-secondary" wire:click="resetUserForm">Cancel</button>
                            </div>
                        </form>
                    @else
                        <p class="small text-secondary mb-0">Choose a user to view and update their roles.</p>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
