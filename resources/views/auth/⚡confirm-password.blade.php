<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth'), Title('Confirm password')] class extends Component {};
?>

<section class="auth-panel">
    <x-auth-brand />
    <div class="auth-card">
        <div class="auth-card__header"><h1 class="auth-card__title">Confirm your password</h1><p class="auth-card__copy">This is a protected action. Enter your password to continue.</p></div>
        <form method="POST" action="{{ route('password.confirm.store') }}" class="auth-form">
            @csrf
            <div class="mb-4"><label for="password" class="form-label">Password</label><div class="auth-password"><input id="password" name="password" type="password" autocomplete="current-password" required class="form-control @error('password') is-invalid @enderror"><button type="button" class="auth-password-toggle" data-password-toggle="#password" aria-label="Show password">Show</button></div>@error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
            <div class="d-grid"><button class="btn btn-primary" type="submit">Confirm password</button></div>
        </form>
    </div>
</section>
