<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('authenticated navigation works without JavaScript errors', function () {
    $user = User::factory()->create([
        'email' => 'browser@example.com',
        'password' => 'password',
    ]);

    $page = visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->click('Sign in')
        ->assertRoute('dashboard')
        ->assertSee('Dashboard')
        ->click('Account')
        ->assertRoute('profile.edit')
        ->assertSee('Profile')
        ->assertNoJavaScriptErrors();

    expect($page)->not->toBeNull();
});
