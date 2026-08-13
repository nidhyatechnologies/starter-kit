<?php

use App\Models\User;
use App\Actions\RecordAuditEvent;
use App\Notifications\AccountSetupInvitation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.app'), Title('Users')] class extends Component {
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $emailVerified = true;

    public bool $isActive = true;

    public bool $mustResetPassword = false;

    public bool $sendInvitation = false;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $roleFilter = '';

    public ?string $successMessage = null;

    /** @var array<int, int> */
    public array $roleIds = [];

    public function mount(): void
    {
        Gate::authorize('users.view');
    }

    #[Computed]
    public function users(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return User::query()
            ->with('roles:id,name')
            ->when($this->search !== '', fn($query) => $query->where(function ($query): void {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            }))
            ->when($this->statusFilter === 'active', fn($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn($query) => $query->where('is_active', false))
            ->when($this->roleFilter !== '', fn($query) => $query->role((int) $this->roleFilter))
            ->orderBy('name')
            ->paginate(12, ['id', 'name', 'email', 'email_verified_at', 'is_active', 'must_reset_password']);
    }

    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::query()->orderBy('name')->get(['id', 'name']);
    }

    public function createUser(): void
    {
        Gate::authorize('users.create');

        $validated = $this->validateUser(requirePassword: !$this->sendInvitation);
        $this->ensureSuperAdminAssignmentIsAllowed();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'email_verified_at' => $this->emailVerified ? now() : null,
            'password' => Hash::make($this->sendInvitation ? Str::password(32) : $validated['password']),
            'is_active' => $this->isActive,
            'must_reset_password' => $this->mustResetPassword || $this->sendInvitation,
        ]);

        $user->syncRoles(Role::query()->whereKey($this->roleIds)->get());

        $sentInvitation = $this->sendInvitation;

        if ($this->sendInvitation) {
            $token = PasswordBroker::broker()->createToken($user);
            $user->notify(new AccountSetupInvitation($token));
        }

        app(RecordAuditEvent::class)->handle('user.created', $user, ['invited' => $this->sendInvitation]);

        $this->resetUserForm();
        $this->resetPage();
        unset($this->users);
        $this->successMessage = $sentInvitation ? 'User created and invitation email sent.' : 'User created successfully.';
    }

    public function editUser(int $userId): void
    {
        Gate::authorize('users.update');

        $user = User::query()->with('roles:id')->findOrFail($userId);
        if (!$this->userCanBeManaged($user)) {
            return;
        }

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->emailVerified = $user->hasVerifiedEmail();
        $this->isActive = $user->is_active;
        $this->mustResetPassword = $user->must_reset_password;
        $this->roleIds = $user->roles->pluck('id')->all();
        $this->resetValidation();
    }

    public function updateUser(): void
    {
        Gate::authorize('users.update');

        $user = User::query()->findOrFail($this->editingUserId);
        if (!$this->userCanBeManaged($user)) {
            return;
        }
        $validated = $this->validateUser(requirePassword: false, user: $user);
        $this->ensureSuperAdminAssignmentIsAllowed();
        $this->ensureLastSuperAdminIsRetained($user);

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'email_verified_at' => $this->emailVerified ? ($user->email_verified_at ?? now()) : null,
            'is_active' => $this->isActive,
            'must_reset_password' => $this->mustResetPassword,
        ];

        if (filled($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        $user->update($attributes);
        $user->syncRoles(Role::query()->whereKey($this->roleIds)->get());
        if (!$user->is_active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        app(RecordAuditEvent::class)->handle('user.updated', $user, ['is_active' => $user->is_active, 'must_reset_password' => $user->must_reset_password]);

        $this->resetUserForm();
        unset($this->users);
        $this->successMessage = 'User updated successfully.';
    }

    public function deleteUser(int $userId): void
    {
        Gate::authorize('users.delete');

        $user = User::query()->findOrFail($userId);
        if (!$this->userCanBeManaged($user)) {
            return;
        }

        if ($user->is(auth()->user())) {
            $this->addError('user', 'You cannot delete your own account from user management.');

            return;
        }

        if ($user->hasRole('Super Admin') && User::role('Super Admin')->count() === 1) {
            $this->addError('user', 'At least one Super Admin account must remain.');

            return;
        }

        $user->delete();
        app(RecordAuditEvent::class)->handle('user.deleted', null, ['deleted_user_id' => $userId]);

        if ($this->editingUserId === $userId) {
            $this->resetUserForm();
        }

        $this->resetPage();
        unset($this->users);
        $this->successMessage = 'User deleted successfully.';
    }

    public function sendPasswordReset(int $userId): void
    {
        Gate::authorize('users.update');

        $user = User::query()->findOrFail($userId);
        if (!$this->userCanBeManaged($user)) {
            return;
        }
        $user->update(['must_reset_password' => true]);
        PasswordBroker::sendResetLink(['email' => $user->email]);
        app(RecordAuditEvent::class)->handle('user.password_reset_requested', $user);
        unset($this->users);
        $this->successMessage = 'Password reset email sent.';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function resetUserForm(): void
    {
        $this->reset('editingUserId', 'name', 'email', 'password', 'passwordConfirmation', 'roleIds', 'isActive', 'mustResetPassword', 'sendInvitation');
        $this->emailVerified = true;
        $this->resetValidation();
    }

    /**
     * @return array{name: string, email: string, password: string, passwordConfirmation: string}
     */
    private function validateUser(bool $requirePassword, ?User $user = null): array
    {
        return $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'password' => [
                Rule::requiredIf($requirePassword && !$this->sendInvitation),
                'nullable',
                'string',
                Password::default(),
                'same:passwordConfirmation',
            ],
            'passwordConfirmation' => [Rule::requiredIf(filled($this->password))],
            'roleIds' => ['array'],
            'roleIds.*' => ['integer', Rule::exists('roles', 'id')],
        ]);
    }

    private function ensureSuperAdminAssignmentIsAllowed(): void
    {
        $superAdmin = Role::query()->where('name', 'Super Admin')->first();

        if ($superAdmin === null) {
            return;
        }

        if (in_array($superAdmin->id, $this->roleIds, true) && !auth()->user()->hasRole($superAdmin)) {
            throw ValidationException::withMessages([
                'roleIds' => 'Only a Super Admin can assign the Super Admin role.',
            ]);
        }
    }

    private function userCanBeManaged(User $user): bool
    {
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            $this->addError('user', 'Only a Super Admin can manage a Super Admin account.');

            return false;
        }

        return true;
    }

    private function ensureLastSuperAdminIsRetained(User $user): void
    {
        $superAdmin = Role::query()->where('name', 'Super Admin')->first();

        if ($superAdmin === null) {
            return;
        }

        if ($user->hasRole($superAdmin) && !in_array($superAdmin->id, $this->roleIds, true) && User::role($superAdmin)->count() === 1) {
            throw ValidationException::withMessages([
                'roleIds' => 'At least one Super Admin account must retain the Super Admin role.',
            ]);
        }
    }
};
?>

<div class="settings-page settings-page--wide users-page">
    <header class="settings-page__header users-page__header">
        <h1 class="settings-page__title">Users</h1>
        <p class="settings-page__description">Manage accounts, access roles, and sign-in status.</p>
    </header>

    <div class="row g-3 g-xl-4 align-items-start">
        <div class="col-xl-8">
            <section class="card settings-card users-card">
                <div class="card-body p-0">
                    <div class="users-card__header">
                        <div>
                            <h2 class="settings-card__title">All users</h2>
                            <p class="users-card__meta">{{ $this->users->total() }}
                                {{ Str::plural('account', $this->users->total()) }}
                            </p>
                        </div>
                    </div>
                    @error('user')
                    <p class="alert alert-danger users-card__alert">{{ $message }}</p> @enderror
                    @if ($successMessage)
                    <p class="alert alert-success users-card__alert">{{ $successMessage }}</p> @endif
                    <div class="users-filter-bar">
                        <div class="users-filter-bar__search">
                            <label for="users-search" class="visually-hidden">Search users</label>
                            <i class="ti ti-search" aria-hidden="true"></i>
                            <input wire:model.live.debounce.300ms="search" id="users-search" type="search"
                                class="form-control" placeholder="Search by name or email">
                        </div>
                        <div class="users-filter-bar__selects">
                            <div>
                                <label for="users-status" class="visually-hidden">Filter by status</label>
                                <select wire:model.live="statusFilter" id="users-status" class="form-select">
                                    <option value="all">All statuses</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Suspended</option>
                                </select>
                            </div>
                            <div>
                                <label for="users-role" class="visually-hidden">Filter by role</label>
                                <select wire:model.live="roleFilter" id="users-role" class="form-select">
                                    <option value="">All roles</option>@foreach ($this->roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table users-table mb-0">
                            {{-- <colgroup>
                                <col class="users-table__user-column">
                                <col class="users-table__roles-column">
                                <col class="users-table__actions-column">
                            </colgroup> --}}
                            <thead>
                                <tr>
                                    <th scope="col">User</th>
                                    <th scope="col">Roles</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->users as $user)
                                    <tr wire:key="user-{{ $user->id }}">
                                        <td class="users-table__user">
                                            <strong>{{ $user->name }}</strong>
                                            <small>{{ $user->email }}</small>
                                            <div class="users-table__status">
                                                @if (!$user->is_active)<span
                                                class="badge text-bg-warning">Suspended</span>@elseif (!$user->hasVerifiedEmail())<span
                                                    class="badge text-bg-light border">Unverified</span>@else<span
                                                    class="badge users-badge--active">Active</span>@endif
                                                @if ($user->must_reset_password)<span
                                                class="badge text-bg-light border">Password reset required</span>@endif
                                            </div>
                                        </td>
                                        <td class="users-table__roles">
                                            @forelse ($user->roles as $role)
                                                <span class="badge text-bg-light border me-1">{{ $role->name }}</span>
                                            @empty
                                                <span class="text-secondary small">No role</span>
                                            @endforelse
                                        </td>
                                        <td class="text-end users-table__actions">
                                            <div class="dropdown">
                                                <button type="button"
                                                    class="btn btn-outline-secondary btn-sm users-table__action-toggle"
                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                    aria-label="Actions for {{ $user->name }}">
                                                    <i class="ti ti-dots" aria-hidden="true"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end users-table__action-menu">
                                                    @can('users.update')
                                                        <button type="button" class="dropdown-item"
                                                            wire:click="editUser({{ $user->id }})"><i class="ti ti-pencil"
                                                                aria-hidden="true"></i>Edit user</button>
                                                        <button type="button" class="dropdown-item"
                                                            wire:click="sendPasswordReset({{ $user->id }})"
                                                            wire:confirm="Send a password reset email and require a new password?"><i
                                                                class="ti ti-key" aria-hidden="true"></i>Send password
                                                            reset</button>
                                                    @endcan
                                                    @can('users.delete')
                                                        @if (!$user->is(auth()->user()))
                                                            <div class="dropdown-divider"></div>
                                                            <button type="button" class="dropdown-item text-danger"
                                                                wire:click="deleteUser({{ $user->id }})"
                                                                wire:confirm="Delete this user account?"><i class="ti ti-trash"
                                                                    aria-hidden="true"></i>Delete user</button>
                                                        @endif
                                                    @endcan
                                                </div>
                                            </div>
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
                    <div class="users-card__footer">{{ $this->users->links() }}</div>
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <section class="card settings-card users-form-card">
                <div class="card-body">
                    <div class="users-form-card__header">
                        <div>
                            <h2 class="settings-card__title">{{ $editingUserId ? 'Edit user' : 'New user' }}</h2>
                            <p class="settings-card__copy">
                                {{ $editingUserId ? 'Update the account details and assigned roles.' : 'Create an account and assign its access roles.' }}
                            </p>
                        </div>
                        @if ($editingUserId)
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                wire:click="resetUserForm">Cancel</button>
                        @endif
                    </div>

                    @if ($editingUserId ? auth()->user()->can('users.update') : auth()->user()->can('users.create'))
                        <form wire:submit="{{ $editingUserId ? 'updateUser' : 'createUser' }}" class="users-form">
                            <section class="users-form__section">
                                <h3 class="users-form__section-title">Account details</h3>
                                <div class="vstack gap-3">
                                    <div>
                                        <label for="user-name" class="form-label">Name</label>
                                        <input wire:model="name" id="user-name" type="text" autocomplete="name"
                                            class="form-control @error('name') is-invalid @enderror">
                                        @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div>
                                        <label for="user-email" class="form-label">Email address</label>
                                        <input wire:model="email" id="user-email" type="email" autocomplete="email"
                                            class="form-control @error('email') is-invalid @enderror">
                                        @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div>
                                        <label for="user-password"
                                            class="form-label">{{ $editingUserId ? 'New password (optional)' : 'Password' }}</label>
                                        <input wire:model="password" id="user-password" type="password"
                                            autocomplete="new-password"
                                            class="form-control @error('password') is-invalid @enderror">
                                        @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div>
                                        <label for="user-password-confirmation" class="form-label">Confirm password</label>
                                        <input wire:model="passwordConfirmation" id="user-password-confirmation"
                                            type="password" autocomplete="new-password"
                                            class="form-control @error('passwordConfirmation') is-invalid @enderror">
                                        @error('passwordConfirmation')
                                        <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </section>
                            <section class="users-form__section">
                                <h3 class="users-form__section-title">Account settings</h3>
                                <div class="users-form__checks">
                                    <label class="form-check">
                                        <input wire:model="emailVerified" class="form-check-input" type="checkbox">
                                        <span class="form-check-label">Mark email address as verified</span>
                                    </label>
                                    <label class="form-check">
                                        <input wire:model="isActive" class="form-check-input" type="checkbox">
                                        <span class="form-check-label">Account is active</span>
                                    </label>
                                    <label class="form-check">
                                        <input wire:model="mustResetPassword" class="form-check-input" type="checkbox">
                                        <span class="form-check-label">Require a password update at next sign in</span>
                                    </label>
                                    @if (!$editingUserId)
                                        <label class="form-check">
                                            <input wire:model.live="sendInvitation" class="form-check-input" type="checkbox">
                                            <span class="form-check-label">Send a password setup email instead of setting a
                                                password</span>
                                        </label>
                                    @endif
                                </div>
                            </section>
                            <section class="users-form__section">
                                <fieldset>
                                    <legend class="users-form__section-title">Roles</legend>
                                    <div class="settings-permission-list settings-permission-list--single">
                                        @foreach ($this->roles as $role)
                                            <label class="settings-permission-option" wire:key="user-role-{{ $role->id }}">
                                                <input wire:model="roleIds" class="form-check-input" type="checkbox"
                                                    value="{{ $role->id }}" @disabled($role->name === 'Super Admin' && !auth()->user()->hasRole('Super Admin'))>
                                                <span>{{ $role->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('roleIds')
                                    <p class="small text-danger mt-2 mb-0">{{ $message }}</p> @enderror
                                </fieldset>
                            </section>
                            <div class="users-form__footer">
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                                    wire:target="createUser,updateUser">
                                    {{ $editingUserId ? 'Save user' : 'Create user' }}
                                </button>
                            </div>
                        </form>
                    @else
                        <p class="small text-secondary mb-0">You do not have permission to create or update user accounts.
                        </p>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
