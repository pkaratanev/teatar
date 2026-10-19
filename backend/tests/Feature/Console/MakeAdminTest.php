<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates an admin with a generated password and shows it once', function () {
    $this->artisan('teatar:make-admin', ['email' => 'Preslav@Example.test'])
        ->expectsOutputToContain('Password (shown only now):')
        ->assertSuccessful();

    $user = User::where('email', 'preslav@example.test')->sole();
    expect($user->is_admin)->toBeTrue()
        ->and($user->username)->toBe('preslav')
        ->and($user->password)->not->toBeNull();
});

it('creates an admin with the given password', function () {
    $this->artisan('teatar:make-admin', ['email' => 'a@example.test', '--password' => 'teatar-2026', '--name' => 'Преслав'])
        ->doesntExpectOutputToContain('shown only now')
        ->assertSuccessful();

    $user = User::where('email', 'a@example.test')->sole();
    expect($user->is_admin)->toBeTrue()
        ->and($user->name)->toBe('Преслав')
        ->and(Hash::check('teatar-2026', $user->password))->toBeTrue();
});

it('promotes an existing account without touching its password', function () {
    $user = User::factory()->create(['email' => 'b@example.test', 'password' => 'old-password']);

    $this->artisan('teatar:make-admin', ['email' => 'b@example.test'])->assertSuccessful();

    $user->refresh();
    expect($user->is_admin)->toBeTrue()
        ->and(Hash::check('old-password', $user->password))->toBeTrue();
});

it('picks a free username when the email name is taken', function () {
    User::factory()->create(['username' => 'maria']);

    $this->artisan('teatar:make-admin', ['email' => 'maria@example.test'])->assertSuccessful();

    expect(User::where('email', 'maria@example.test')->value('username'))->toBe('maria2');
});

it('refuses a short password and a bad email', function () {
    $this->artisan('teatar:make-admin', ['email' => 'c@example.test', '--password' => 'short'])->assertFailed();
    $this->artisan('teatar:make-admin', ['email' => 'not-an-email'])->assertFailed();

    expect(User::count())->toBe(0);
});
