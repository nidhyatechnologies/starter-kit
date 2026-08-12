<?php

use App\Models\User;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Password')] class extends Component {
    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $saved = false;

    public function updatePassword(): void
    {
        /** @var User $user */
        $user = auth()->user();

        app(UpdatesUserPasswords::class)->update($user, [
            'current_password' => $this->currentPassword,
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
        ]);

        $this->reset('currentPassword', 'password', 'passwordConfirmation');
        $this->saved = true;
    }
};
?>

<div class="settings-page">
    <header class="settings-page__header">
        <h1 class="settings-page__title">Password</h1>
        <p class="settings-page__description">Use a strong, unique password to keep your account protected.</p>
    </header>

    <section class="card settings-card">
        <div class="card-body">
            <h2 class="settings-card__title">Change password</h2>
            <p class="settings-card__copy">Choose a password you do not use on any other service.</p>

            <form wire:submit="updatePassword">
                <div class="vstack gap-3">
                    <div>
                        <label for="current-password" class="form-label">Current password</label>
                        <input wire:model="currentPassword" id="current-password" type="password" autocomplete="current-password" class="form-control @error('current_password') is-invalid @enderror">
                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="password" class="form-label">New password</label>
                        <input wire:model="password" id="password" type="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="password-confirmation" class="form-label">Confirm new password</label>
                        <input wire:model="passwordConfirmation" id="password-confirmation" type="password" autocomplete="new-password" class="form-control">
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mt-4">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="updatePassword">
                        <span wire:loading.remove wire:target="updatePassword">Update password</span>
                        <span wire:loading wire:target="updatePassword">Updating...</span>
                    </button>
                    @if ($saved) <span class="small text-success">Password updated.</span> @endif
                </div>
            </form>
        </div>
    </section>
</div>
