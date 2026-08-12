<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth'), Title('Sign in')] class extends Component { };
?>

<section class="auth-panel">
    <x-auth-brand />

    <div class="auth-card">
        <div class="auth-card__header">
            <h1 class="auth-card__title">Welcome back</h1>
            <p class="auth-card__copy">Sign in to continue to your account.</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="auth-form">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                    class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="email">
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="auth-password">
                    <input id="password" name="password" type="password"
                        class="form-control @error('password') is-invalid @enderror" required
                        autocomplete="current-password">
                    <button type="button" class="auth-password-toggle" data-password-toggle="#password"
                        aria-label="Show password">Show</button>
                </div>
                @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="auth-actions">
                <div class="form-check">
                    <input id="remember" name="remember" type="checkbox" value="1" class="form-check-input">
                    <label for="remember" class="form-check-label">Remember me</label>
                </div>
                <a wire:navigate href="{{ route('password.request') }}" class="auth-link">Forgot password?</a>
            </div>

            <div class="d-grid"><button class="btn btn-primary" type="submit">Sign in</button></div>
        </form>

        <x-social-auth-options />
        <p class="auth-footer">New to NatyaTech? <a wire:navigate href="{{ route('register') }}"
                class="auth-link">Create an account</a></p>
    </div>
</section>