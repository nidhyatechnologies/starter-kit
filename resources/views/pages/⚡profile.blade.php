<?php

use App\Models\User;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Profile settings')] class extends Component {
    public string $name = '';

    public string $email = '';

    public bool $saved = false;

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function updateProfile(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
        ]);

        app(UpdatesUserProfileInformation::class)->update($user, [
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->saved = true;
    }
};
?>

<div class="settings-page">
    <header class="settings-page__header">
        <h1 class="settings-page__title">Profile</h1>
        <p class="settings-page__description">Manage the details used across your NatyaTech account.</p>
    </header>

    <section class="card settings-card">
        <div class="card-body">
            <h2 class="settings-card__title">Personal details</h2>
            <p class="settings-card__copy">Your name and email address identify your account.</p>

            <form wire:submit="updateProfile">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name</label>
                        <input wire:model="name" id="name" type="text" autocomplete="name" class="form-control @error('name') is-invalid @enderror">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email address</label>
                        <input wire:model="email" id="email" type="email" autocomplete="email" class="form-control @error('email') is-invalid @enderror">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mt-4">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="updateProfile">
                        <span wire:loading.remove wire:target="updateProfile">Save changes</span>
                        <span wire:loading wire:target="updateProfile">Saving...</span>
                    </button>
                    @if ($saved) <span class="small text-success">Profile saved.</span> @endif
                </div>
            </form>
        </div>
    </section>
</div>
