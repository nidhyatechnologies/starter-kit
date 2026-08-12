<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth'), Title('Two-factor authentication')] class extends Component {};
?>

<section class="auth-panel">
    <x-auth-brand />
    <div class="auth-card">
        <div class="auth-card__header"><h1 class="auth-card__title">Two-factor authentication</h1><p class="auth-card__copy">Enter a code from your authenticator app or use a recovery code.</p></div>
        <form method="POST" action="{{ route('two-factor.login.store') }}" class="auth-form">
            @csrf
            <div class="mb-3"><label for="code" class="form-label">Authentication code</label><input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" class="form-control @error('code') is-invalid @enderror">@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-4"><label for="recovery_code" class="form-label">Recovery code</label><input id="recovery_code" name="recovery_code" autocomplete="one-time-code" class="form-control @error('recovery_code') is-invalid @enderror">@error('recovery_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="d-grid"><button class="btn btn-primary" type="submit">Continue</button></div>
        </form>
    </div>
</section>
