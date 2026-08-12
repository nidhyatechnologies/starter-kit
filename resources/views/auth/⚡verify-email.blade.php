<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth'), Title('Verify your email')] class extends Component {};
?>

<section class="auth-panel">
    <x-auth-brand />
    <div class="auth-card">
        <div class="auth-card__header"><h1 class="auth-card__title">Verify your email</h1><p class="auth-card__copy">We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Open it to activate your account.</p></div>
        @if (session('status') === 'verification-link-sent')<div class="alert alert-success mb-4">A new verification link has been sent.</div>@endif
        <form method="POST" action="{{ route('verification.send') }}" class="d-grid">@csrf<button class="btn btn-primary" type="submit">Resend verification email</button></form>
        <form method="POST" action="{{ route('logout') }}" class="d-grid mt-3">@csrf<button class="btn btn-outline-secondary" type="submit">Sign out</button></form>
    </div>
</section>
