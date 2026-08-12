<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth'), Title('Forgot password')] class extends Component {};
?>

<section class="auth-panel">
    <x-auth-brand />
    <div class="auth-card">
        <div class="auth-card__header"><h1 class="auth-card__title">Reset your password</h1><p class="auth-card__copy">Enter your email address and we’ll send you a reset link.</p></div>
        @if (session('status'))<div class="alert alert-success mb-4">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('password.email') }}" class="auth-form">
            @csrf
            <div class="mb-4"><label for="email" class="form-label">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="form-control @error('email') is-invalid @enderror">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="d-grid"><button class="btn btn-primary" type="submit">Email reset link</button></div>
        </form>
        <p class="auth-footer"><a wire:navigate href="{{ route('login') }}" class="auth-link">Back to sign in</a></p>
    </div>
</section>
