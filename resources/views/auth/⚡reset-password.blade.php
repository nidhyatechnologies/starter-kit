<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth'), Title('Choose a new password')] class extends Component {
    public string $token = '';

    public bool $isAccountSetup = false;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->isAccountSetup = request()->boolean('setup');
    }
};
?>

<section class="auth-panel">
    <x-auth-brand />
    <div class="auth-card">
        <div class="auth-card__header"><h1 class="auth-card__title">{{ $isAccountSetup ? 'Set up your account' : 'Choose a new password' }}</h1><p class="auth-card__copy">{{ $isAccountSetup ? 'Create a password to activate your new account.' : 'Create a strong password for your account.' }}</p></div>
        <form method="POST" action="{{ route('password.update') }}" class="auth-form">
            @csrf
            <input name="token" type="hidden" value="{{ $token }}">
            <div class="mb-3"><label for="email" class="form-label">Email address</label><input id="email" name="email" type="email" value="{{ old('email', request('email')) }}" autocomplete="email" required class="form-control @error('email') is-invalid @enderror">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label for="password" class="form-label">New password</label><div class="auth-password"><input id="password" name="password" type="password" autocomplete="new-password" required class="form-control @error('password') is-invalid @enderror"><button type="button" class="auth-password-toggle" data-password-toggle="#password" aria-label="Show password">Show</button></div>@error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
            <div class="mb-4"><label for="password_confirmation" class="form-label">Confirm new password</label><div class="auth-password"><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="form-control"><button type="button" class="auth-password-toggle" data-password-toggle="#password_confirmation" aria-label="Show password">Show</button></div></div>
            <div class="d-grid"><button class="btn btn-primary" type="submit">{{ $isAccountSetup ? 'Set up account' : 'Reset password' }}</button></div>
        </form>
    </div>
</section>
