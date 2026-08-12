<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::livewire('/dashboard', 'pages::dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::livewire('/profile', 'pages::profile')
    ->middleware(['auth', 'verified'])
    ->name('profile.edit');

Route::livewire('/profile/password', 'pages::password')
    ->middleware(['auth', 'verified'])
    ->name('profile.password');

Route::livewire('/profile/security', 'pages::security')
    ->middleware(['auth', 'verified'])
    ->name('profile.security');

Route::livewire('/access-control', 'pages::access-control')
    ->middleware(['auth', 'verified', 'can:manage access'])
    ->name('access-control.index');

Route::livewire('/users', 'pages::users')
    ->middleware(['auth', 'verified', 'can:manage access'])
    ->name('users.index');

Route::livewire('/login', 'auth::login')->name('login');
Route::livewire('/register', 'auth::register')->name('register');
Route::livewire('/forgot-password', 'auth::forgot-password')->name('password.request');
Route::livewire('/reset-password/{token}', 'auth::reset-password')->name('password.reset');
Route::livewire('/email/verify', 'auth::verify-email')->middleware('auth')->name('verification.notice');
Route::livewire('/user/confirm-password', 'auth::confirm-password')->middleware('auth')->name('password.confirm');
Route::livewire('/two-factor-challenge', 'auth::two-factor-challenge')->middleware('guest')->name('two-factor.login');
