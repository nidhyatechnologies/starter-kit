<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Security')] class extends Component {
    public string $currentPassword = '';

    public string $code = '';

    public bool $twoFactorEnabled = false;

    public bool $twoFactorConfirmed = false;

    /** @var array<int, string> */
    public array $recoveryCodes = [];

    public function mount(): void
    {
        $this->refreshTwoFactorState();
    }

    public function enableTwoFactor(): void
    {
        $this->validateCurrentPassword();

        app(EnableTwoFactorAuthentication::class)(auth()->user());

        $this->currentPassword = '';
        $this->refreshTwoFactorState();
    }

    public function confirmTwoFactor(): void
    {
        $this->validate(['code' => ['required', 'string']]);

        app(ConfirmTwoFactorAuthentication::class)(auth()->user(), $this->code);

        $this->code = '';
        $this->refreshTwoFactorState();
    }

    public function regenerateRecoveryCodes(): void
    {
        $this->validateCurrentPassword();

        app(GenerateNewRecoveryCodes::class)(auth()->user());

        $this->currentPassword = '';
        $this->refreshTwoFactorState();
    }

    public function disableTwoFactor(): void
    {
        $this->validateCurrentPassword();

        app(DisableTwoFactorAuthentication::class)(auth()->user());

        $this->currentPassword = '';
        $this->recoveryCodes = [];
        $this->refreshTwoFactorState();
    }

    public function closeAccount(): void
    {
        $this->validateCurrentPassword();

        /** @var User $user */
        $user = auth()->user();

        Auth::logout();
        $user->delete();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirectRoute('home', navigate: true);
    }

    private function validateCurrentPassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
        ]);
    }

    private function refreshTwoFactorState(): void
    {
        /** @var User $user */
        $user = auth()->user()->fresh();

        auth()->setUser($user);

        $this->twoFactorEnabled = filled($user->two_factor_secret);
        $this->twoFactorConfirmed = filled($user->two_factor_confirmed_at);
        $this->recoveryCodes = $this->twoFactorConfirmed ? $user->recoveryCodes() : [];
    }
};
?>

<div class="settings-page">
    <header class="settings-page__header">
        <h1 class="settings-page__title">Security</h1>
        <p class="settings-page__description">Add another layer of protection and manage sensitive account actions.</p>
    </header>

    <section class="card settings-card">
        <div class="card-body">
            <h2 class="settings-card__title">Two-factor authentication</h2>
            <p class="settings-card__copy">Use an authenticator app to verify your identity whenever you sign in.</p>

            @if (! $twoFactorEnabled)
                <form wire:submit="enableTwoFactor">
                    <label for="enable-current-password" class="form-label">Current password</label>
                    <input wire:model="currentPassword" id="enable-current-password" type="password" autocomplete="current-password" class="form-control @error('currentPassword') is-invalid @enderror">
                    @error('currentPassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <button type="submit" class="btn btn-primary mt-3" wire:loading.attr="disabled" wire:target="enableTwoFactor">Enable two-factor authentication</button>
                </form>
            @elseif (! $twoFactorConfirmed)
                <p class="small text-secondary mb-3">Scan this code with your authenticator app, then enter the six-digit code it provides.</p>
                <div class="settings-qr mb-4">{!! auth()->user()->twoFactorQrCodeSvg() !!}</div>
                <form wire:submit="confirmTwoFactor">
                    <label for="two-factor-code" class="form-label">Authentication code</label>
                    <input wire:model="code" id="two-factor-code" type="text" inputmode="numeric" autocomplete="one-time-code" class="form-control @error('code') is-invalid @enderror">
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <button type="submit" class="btn btn-primary mt-3" wire:loading.attr="disabled" wire:target="confirmTwoFactor">Confirm and enable</button>
                </form>
            @else
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <p class="mb-0 small text-success">Two-factor authentication is enabled.</p>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#disable-two-factor">Disable</button>
                </div>
                <div class="collapse mt-3" id="disable-two-factor">
                    <form wire:submit="disableTwoFactor" class="border-top pt-3">
                        <label for="disable-current-password" class="form-label">Current password</label>
                        <input wire:model="currentPassword" id="disable-current-password" type="password" autocomplete="current-password" class="form-control @error('currentPassword') is-invalid @enderror">
                        @error('currentPassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <button type="submit" class="btn btn-outline-danger mt-3" wire:loading.attr="disabled" wire:target="disableTwoFactor">Disable two-factor authentication</button>
                    </form>
                </div>

                <div class="border-top mt-4 pt-4">
                    <h3 class="settings-card__title fs-6">Recovery codes</h3>
                    <p class="settings-card__copy mb-0">Store these codes somewhere safe. Each code can be used once if you lose your device.</p>
                    <ul class="settings-recovery-codes">
                        @foreach ($recoveryCodes as $recoveryCode)
                            <li wire:key="recovery-code-{{ $recoveryCode }}">{{ $recoveryCode }}</li>
                        @endforeach
                    </ul>
                    <form wire:submit="regenerateRecoveryCodes" class="mt-3">
                        <label for="recovery-current-password" class="visually-hidden">Current password</label>
                        <input wire:model="currentPassword" id="recovery-current-password" type="password" autocomplete="current-password" placeholder="Current password" class="form-control @error('currentPassword') is-invalid @enderror">
                        @error('currentPassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <button type="submit" class="btn btn-outline-secondary btn-sm mt-2" wire:loading.attr="disabled" wire:target="regenerateRecoveryCodes">Generate new codes</button>
                    </form>
                </div>
            @endif
        </div>
    </section>

    <section class="card settings-card settings-card--danger">
        <div class="card-body">
            <h2 class="settings-card__title">Close account</h2>
            <p class="settings-card__copy">This permanently deletes your account and cannot be undone.</p>
            <form wire:submit="closeAccount">
                <label for="close-current-password" class="form-label">Current password</label>
                <input wire:model="currentPassword" id="close-current-password" type="password" autocomplete="current-password" class="form-control @error('currentPassword') is-invalid @enderror">
                @error('currentPassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <button type="submit" class="btn btn-danger mt-3" wire:loading.attr="disabled" wire:target="closeAccount">Close account</button>
            </form>
        </div>
    </section>
</div>
