<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Dashboard')] class extends Component { };
?>

<div class="row g-4">
    <div class="col-12">
        <section class="bg-gradient-mixed p-5 p-lg-6 rounded-3">
            <p class="text-uppercase small fw-semibold mb-2">Overview</p>
            <h1 class="fs-2 mb-2">Welcome back, {{ auth()->user()->name }}.</h1>
            <p class="mb-0">Your Nidhya Starter Kit workspace is ready. Manage your account or begin adding application modules
                from here.</p>
        </section>
    </div>
    <div class="col-md-4">
        <article class="card h-100">
            <div class="card-body"><span class="badge text-bg-primary mb-4">Account</span>
                <p class="text-secondary mb-2">Account status</p>
                <h2 class="fs-5 mb-0">Email verified</h2>
            </div>
        </article>
    </div>
    <div class="col-md-4">
        <article class="card h-100">
            <div class="card-body"><span class="badge text-bg-secondary mb-4">Brand</span>
                <p class="text-secondary mb-2">Appearance</p>
                <h2 class="fs-5 mb-0">Nidhya Starter Kit design system</h2>
            </div>
        </article>
    </div>
    <div class="col-md-4">
        <article class="card h-100">
            <div class="card-body"><span class="badge text-bg-info mb-4">Settings</span>
                <p class="text-secondary mb-2">Account</p><a wire:navigate href="{{ route('profile.edit') }}"
                    class="fs-5 fw-semibold text-decoration-none">Update your profile</a>
            </div>
        </article>
    </div>
</div>
