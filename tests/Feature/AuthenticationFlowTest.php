<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('guests can access the Livewire authentication screens', function () {
    $this->get(route('login'))->assertSuccessful();
    $this->get(route('register'))->assertSuccessful();
    $this->get(route('password.request'))->assertSuccessful();
    $this->get(route('password.reset', 'reset-token'))->assertSuccessful();
});

test('a verified user can sign in from the login screen', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'password',
    ]);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('a user can register and receives an email verification notification', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Taylor Otwell',
        'email' => 'taylor@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $user = User::where('email', 'taylor@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('unverified users are sent to email verification before the dashboard', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('verified users can view the Livewire dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Workspace')
        ->assertSee('Notifications')
        ->assertSee('Overview')
        ->assertSee('Your NatyaTech workspace is ready');
});

test('users can update their profile from the admin area', function () {
    $user = User::factory()->create(['name' => 'Original Name']);

    $this->actingAs($user);

    Livewire::test('pages::profile')
        ->set('name', 'Updated Name')
        ->set('email', $user->email)
        ->call('updateProfile')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    expect($user->fresh()->name)->toBe('Updated Name');
});

test('users can manage their password and security settings', function () {
    $user = User::factory()->create(['password' => 'current-password']);

    $this->actingAs($user);

    $this->get(route('profile.password'))
        ->assertSuccessful()
        ->assertSee('Change password');

    $this->get(route('profile.security'))
        ->assertSuccessful()
        ->assertSee('Two-factor authentication')
        ->assertSee('Close account');

    Livewire::test('pages::password')
        ->set('currentPassword', 'current-password')
        ->set('password', 'new-secure-password')
        ->set('passwordConfirmation', 'new-secure-password')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    expect($user->fresh()->password)->not->toBe('current-password');
});

test('a user can request a password reset link', function () {
    $user = User::factory()->create();
    Notification::fake();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});
