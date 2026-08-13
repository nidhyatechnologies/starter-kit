<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

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
        ->assertSee('Dashboard')
        ->assertSee('Notifications')
        ->assertSee('Overview')
        ->assertSee('Your Nidhya Starter Kit workspace is ready');
});

test('suspended users cannot sign in and users required to reset their password are redirected', function () {
    $suspendedUser = User::factory()->create([
        'email' => 'suspended@example.com',
        'password' => 'password',
        'is_active' => false,
    ]);
    $passwordResetUser = User::factory()->create(['must_reset_password' => true]);

    $this->post(route('login.store'), [
        'email' => $suspendedUser->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->actingAs($passwordResetUser)
        ->get(route('dashboard'))
        ->assertRedirect(route('profile.password'));

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => $passwordResetUser->email,
        'password' => 'password',
    ])->assertRedirect(route('profile.password'));

    $this->assertAuthenticatedAs($passwordResetUser);
});

test('users can update their profile from the admin area', function () {
    $user = User::factory()->create(['name' => 'Original Name']);

    $this->actingAs($user);

    Livewire::test('pages::profile.index')
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

    Livewire::test('pages::profile.password')
        ->set('currentPassword', 'current-password')
        ->set('password', 'new-secure-password')
        ->set('passwordConfirmation', 'new-secure-password')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    expect($user->fresh()->password)->not->toBe('current-password');
});

test('a user can enable, confirm, and use two-factor authentication', function () {
    $user = User::factory()->create(['password' => 'current-password']);

    $this->actingAs($user);

    Livewire::test('pages::profile.security')
        ->set('currentPassword', 'current-password')
        ->call('enableTwoFactor')
        ->assertHasNoErrors();

    $secret = Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret);
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    Livewire::test('pages::profile.security')
        ->set('code', $code)
        ->call('confirmTwoFactor')
        ->assertHasNoErrors();

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();

    $recoveryCode = $user->fresh()->recoveryCodes()[0];

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'current-password',
    ])->assertRedirect(route('two-factor.login'));

    $this->post(route('two-factor.login.store'), ['recovery_code' => $recoveryCode])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('a user can permanently close their own account', function () {
    $user = User::factory()->create(['password' => 'current-password']);

    $this->actingAs($user);

    Livewire::test('pages::profile.security')
        ->set('currentPassword', 'current-password')
        ->call('closeAccount')
        ->assertHasNoErrors();

    $this->assertGuest();
    $this->assertModelMissing($user);
});

test('a user can request a password reset link', function () {
    $user = User::factory()->create();
    Notification::fake();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});
