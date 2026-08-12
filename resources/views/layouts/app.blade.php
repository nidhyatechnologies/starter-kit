<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="app-page">
    <header class="app-header">
        <div class="app-header__inner">
            <a wire:navigate href="{{ route('dashboard') }}" class="app-header__brand">
                <img src="{{ asset('logo_without_tagline.png') }}" alt="NatyaTech">
            </a>

            <div class="app-header__actions">
                <div class="dropdown">
                    <button class="app-icon-button position-relative" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false" aria-label="Notifications">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
                        </svg>
                        <span class="app-icon-button__indicator"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end app-notification-menu">
                        <p class="app-notification-menu__title">Notifications</p>
                        <p class="app-notification-menu__empty">You’re all caught up.</p>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="app-user-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="app-user-menu__avatar">{{ auth()->user()->initials() }}</span>
                        <span class="app-user-menu__name">{{ auth()->user()->name }}</span>
                        <svg class="app-user-menu__chevron" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end app-profile-dropdown">
                        <div class="app-profile-dropdown__identity">
                            <span class="app-profile-dropdown__avatar">{{ auth()->user()->initials() }}</span>
                            <span class="app-profile-dropdown__details">
                                <strong>{{ auth()->user()->name }}</strong>
                                <small>{{ auth()->user()->email }}</small>
                            </span>
                        </div>
                        <div class="app-profile-dropdown__actions">
                            <a wire:navigate class="dropdown-item" href="{{ route('profile.edit') }}">Profile settings</a>
                        </div>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <nav class="app-primary-nav" aria-label="Main navigation">
        <div class="app-primary-nav__inner">
            <a wire:navigate href="{{ route('dashboard') }}" @class(['app-primary-nav__link', 'is-active' => request()->routeIs('dashboard')])>Dashboard</a>
            <a wire:navigate href="{{ route('profile.edit') }}" @class(['app-primary-nav__link', 'is-active' => request()->routeIs('profile.*')])>Account</a>
            @can('manage access')
                <a wire:navigate href="{{ route('users.index') }}" @class(['app-primary-nav__link', 'is-active' => request()->routeIs('users.*') || request()->routeIs('access-control.*')])>User management</a>
            @endcan
        </div>
    </nav>

    <div @class(['app-workspace', 'app-workspace--dashboard' => request()->routeIs('dashboard')])>
        @if (request()->routeIs('profile.*'))
            <aside class="app-sidebar" aria-label="Account navigation">
                <p class="app-sidebar__title">Account</p>
                <nav class="app-sidebar__nav">
                    <a wire:navigate href="{{ route('profile.edit') }}" @class(['app-sidebar__link', 'is-active' => request()->routeIs('profile.edit')])>
                        <i class="ti ti-user" aria-hidden="true"></i>
                        <span>Profile</span>
                    </a>
                    <a wire:navigate href="{{ route('profile.password') }}" @class(['app-sidebar__link', 'is-active' => request()->routeIs('profile.password')])>
                        <i class="ti ti-key" aria-hidden="true"></i>
                        <span>Password</span>
                    </a>
                    <a wire:navigate href="{{ route('profile.security') }}" @class(['app-sidebar__link', 'is-active' => request()->routeIs('profile.security')])>
                        <i class="ti ti-shield-lock" aria-hidden="true"></i>
                        <span>Security</span>
                    </a>
                </nav>
            </aside>
        @elseif (request()->routeIs('users.*') || request()->routeIs('access-control.*'))
            <aside class="app-sidebar" aria-label="User management navigation">
                <p class="app-sidebar__title">User management</p>
                <nav class="app-sidebar__nav">
                    <a wire:navigate href="{{ route('users.index') }}" @class(['app-sidebar__link', 'is-active' => request()->routeIs('users.*')])>
                        <i class="ti ti-users" aria-hidden="true"></i>
                        <span>Users</span>
                    </a>
                    <a wire:navigate href="{{ route('access-control.index') }}" @class(['app-sidebar__link', 'is-active' => request()->routeIs('access-control.*')])>
                        <i class="ti ti-lock-access" aria-hidden="true"></i>
                        <span>Roles & permissions</span>
                    </a>
                </nav>
            </aside>
        @endif

        <main class="app-content">
            <div class="container-fluid">{{ $slot }}</div>
        </main>
    </div>

    @livewireScripts
</body>

</html>
