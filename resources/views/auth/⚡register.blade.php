<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth'), Title('Create account')] class extends Component {};
?>

<section class="auth-panel">
    <x-auth-brand />

    <div class="auth-card">
        <div class="auth-card__header">
            <h1 class="auth-card__title">Create your account</h1>
            <p class="auth-card__copy">Use your email address to get started.</p>
        </div>

        <form method="POST" action="{{ route('register.store') }}" class="auth-form">
            @csrf
            <div class="mb-3"><label for="name" class="form-label">Name</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required class="form-control @error('name') is-invalid @enderror">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label for="email" class="form-label">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="form-control @error('email') is-invalid @enderror">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label for="password" class="form-label">Password</label><div class="auth-password"><input id="password" name="password" type="password" autocomplete="new-password" required class="form-control @error('password') is-invalid @enderror"><button type="button" class="auth-password-toggle" data-password-toggle="#password" aria-label="Show password">Show</button></div>@error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
            <div class="mb-4"><label for="password_confirmation" class="form-label">Confirm password</label><div class="auth-password"><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="form-control"><button type="button" class="auth-password-toggle" data-password-toggle="#password_confirmation" aria-label="Show password">Show</button></div></div>
            <div class="d-grid"><button class="btn btn-primary" type="submit">Create account</button></div>
        </form>

        <p class="auth-footer">Already registered? <a wire:navigate href="{{ route('login') }}" class="auth-link">Sign in</a></p>
    </div>
</section>
