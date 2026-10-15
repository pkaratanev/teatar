<?php

use App\Models\User;

it('registers, reads the profile, and logs out with a token', function () {
    $token = $this->postJson('/api/v1/auth/register', [
        'name' => 'Мария',
        'username' => 'maria',
        'email' => 'maria@example.test',
        'password' => 'teatar-2026',
        'device_name' => 'iPhone',
    ])->assertCreated()->json('token');

    $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.email', 'maria@example.test');
    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
});

it('rejects a wrong password', function () {
    User::factory()->create(['email' => 'a@example.test']);

    $this->postJson('/api/v1/auth/login', ['email' => 'a@example.test', 'password' => 'nope', 'device_name' => 'x'])
        ->assertUnprocessable();
});

it('deletes the account and everything attached to it', function () {
    $user = User::factory()->create();
    $token = $user->createToken('x')->plainTextToken;

    $this->withToken($token)->deleteJson('/api/v1/me')->assertNoContent();

    expect(User::find($user->id))->toBeNull();
});
