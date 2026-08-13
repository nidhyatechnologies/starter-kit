<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::livewire('/dashboard', 'pages::dashboard')
    ->middleware(['auth', 'verified', 'password.update.required'])
    ->name('dashboard');

Route::livewire('/profile', 'pages::profile.index')
    ->middleware(['auth', 'verified', 'password.update.required'])
    ->name('profile.edit');

Route::livewire('/profile/password', 'pages::profile.password')
    ->middleware(['auth', 'verified'])
    ->name('profile.password');

Route::livewire('/profile/security', 'pages::profile.security')
    ->middleware(['auth', 'verified', 'password.update.required'])
    ->name('profile.security');

Route::livewire('/access-control', 'pages::roles.index')
    ->middleware(['auth', 'verified', 'password.update.required'])
    ->name('access-control.index');

Route::livewire('/users', 'pages::users.index')
    ->middleware(['auth', 'verified', 'password.update.required', 'can:users.view'])
    ->name('users.index');

Route::livewire('/audit-log', 'pages::audit.index')
    ->middleware(['auth', 'verified', 'password.update.required', 'can:audit.view'])
    ->name('audit.index');

Route::livewire('/login', 'auth::login')->name('login');
Route::livewire('/register', 'auth::register')->name('register');
Route::livewire('/forgot-password', 'auth::forgot-password')->name('password.request');
Route::livewire('/reset-password/{token}', 'auth::reset-password')->name('password.reset');
Route::livewire('/email/verify', 'auth::verify-email')->middleware('auth')->name('verification.notice');
Route::livewire('/user/confirm-password', 'auth::confirm-password')->middleware('auth')->name('password.confirm');
Route::livewire('/two-factor-challenge', 'auth::two-factor-challenge')->middleware('guest')->name('two-factor.login');
