<?php

use App\Models\User;
use App\Actions\RecordAuditEvent;
use Illuminate\Support\Facades\DB;
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

    /** @var array<int, array{id: string, ip_address: string|null, user_agent: string|null, last_activity: int, is_current: bool}> */
    public array $sessions = [];

    public function mount(): void
    {
        $this->refreshTwoFactorState();
        $this->refreshSessions();
    }

    public function enableTwoFactor(): void
    {
        $this->validateCurrentPassword();

        app(EnableTwoFactorAuthentication::class)(auth()->user());
        app(RecordAuditEvent::class)->handle('two_factor.enabled', auth()->user());

        $this->currentPassword = '';
        $this->refreshTwoFactorState();
    }

    public function confirmTwoFactor(): void
    {
        $this->validate(['code' => ['required', 'string']]);

        app(ConfirmTwoFactorAuthentication::class)(auth()->user(), $this->code);
        app(RecordAuditEvent::class)->handle('two_factor.confirmed', auth()->user());

        $this->code = '';
        $this->refreshTwoFactorState();
    }

    public function regenerateRecoveryCodes(): void
    {
        $this->validateCurrentPassword();

        app(GenerateNewRecoveryCodes::class)(auth()->user());
        app(RecordAuditEvent::class)->handle('two_factor.recovery_codes_regenerated', auth()->user());

        $this->currentPassword = '';
        $this->refreshTwoFactorState();
    }

    public function disableTwoFactor(): void
    {
        $this->validateCurrentPassword();

        app(DisableTwoFactorAuthentication::class)(auth()->user());
        app(RecordAuditEvent::class)->handle('two_factor.disabled', auth()->user());

        $this->currentPassword = '';
        $this->recoveryCodes = [];
        $this->refreshTwoFactorState();
    }

    public function closeAccount(): void
    {
        $this->validateCurrentPassword();

        /** @var User $user */
        $user = auth()->user();

        if ($user->isSuperAdmin() && User::role('Super Admin')->count() === 1) {
            $this->addError('currentPassword', 'At least one Super Admin account must remain.');

            return;
        }

        app(RecordAuditEvent::class)->handle('account.closed', $user);
        Auth::logout();
        $user->delete();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        $this->redirectRoute('home', navigate: true);
    }

    public function revokeSession(string $sessionId): void
    {
        if ($sessionId === request()->session()->getId()) {
            $this->addError('sessions', 'Use sign out to end the current session.');

            return;
        }

        DB::table('sessions')->where('id', $sessionId)->where('user_id', auth()->id())->delete();
        app(RecordAuditEvent::class)->handle('session.revoked', auth()->user(), ['session_id' => $sessionId]);
        $this->refreshSessions();
    }

    public function revokeOtherSessions(): void
    {
        $this->validateCurrentPassword();
        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', request()->session()->getId())
            ->delete();
        app(RecordAuditEvent::class)->handle('sessions.revoked_other', auth()->user());
        $this->currentPassword = '';
        $this->refreshSessions();
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

    private function refreshSessions(): void
    {
        if (! request()->hasSession()) {
            $this->sessions = [];

            return;
        }

        $currentSessionId = request()->session()->getId();

        $this->sessions = DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $session): array => [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => $session->last_activity,
                'is_current' => $session->id === $currentSessionId,
            ])
            ->all();
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

    <section class="card settings-card">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div><h2 class="settings-card__title">Active sessions</h2><p class="settings-card__copy mb-0">Review the devices currently signed in to your account.</p></div>
            </div>
            @error('sessions') <p class="small text-danger mt-2 mb-0">{{ $message }}</p> @enderror
            <div class="vstack gap-2 mt-3">
                @forelse ($sessions as $session)
                    <div class="settings-list-row" wire:key="session-{{ $session['id'] }}">
                        <div><strong>{{ $session['is_current'] ? 'Current session' : 'Signed-in device' }}</strong><small>{{ $session['ip_address'] ?? 'Unknown IP' }} · {{ \Illuminate\Support\Carbon::createFromTimestamp($session['last_activity'])->diffForHumans() }}</small></div>
                        @if (! $session['is_current'])<button type="button" class="btn btn-outline-danger btn-sm" wire:click="revokeSession('{{ $session['id'] }}')">Revoke</button>@endif
                    </div>
                @empty
                    <p class="small text-secondary mb-0">Session details are available when using the database session driver.</p>
                @endforelse
            </div>
            @if (count($sessions) > 1)
                <form wire:submit="revokeOtherSessions" class="mt-3"><label for="sessions-current-password" class="visually-hidden">Current password</label><input wire:model="currentPassword" id="sessions-current-password" type="password" autocomplete="current-password" placeholder="Current password" class="form-control @error('currentPassword') is-invalid @enderror"><button type="submit" class="btn btn-outline-secondary btn-sm mt-2">Sign out of other sessions</button></form>
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
