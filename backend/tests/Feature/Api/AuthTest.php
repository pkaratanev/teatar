<?php

use App\Models\User;

it('registers, reads the profile, and logs out with a token', function () {
    $token = $this->postJson('/api/v1/auth/register', [
        'name' => 'Мария',
        'username' => 'maria',
        'email' => 'maria@example.test',
        'password' => 'teatar-2026',
        'device_name' => 'iPhone',
    ])->assertCreated()->assertJsonPath('user.email', 'maria@example.test')->json('token');

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

it('edits the display name and bio', function () {
    $user = User::factory()->create(['name' => 'Мария', 'bio' => null]);

    $this->actingAs($user)->patchJson('/api/v1/me', ['name' => '  Мария Петрова ', 'bio' => 'Ходя на театър всяка седмица.'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Мария Петрова')
        ->assertJsonPath('data.bio', 'Ходя на театър всяка седмица.');

    $this->actingAs($user)->patchJson('/api/v1/me', ['bio' => '   '])->assertOk()->assertJsonPath('data.bio', null);
    expect($user->fresh()->name)->toBe('Мария Петрова');
});

it('rejects an empty name, an overlong bio and other fields', function () {
    $user = User::factory()->create(['username' => 'maria', 'email' => 'maria@example.test']);

    $this->actingAs($user)->patchJson('/api/v1/me', ['name' => ''])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->actingAs($user)->patchJson('/api/v1/me', ['bio' => str_repeat('а', 501)])->assertUnprocessable()->assertJsonValidationErrors('bio');

    $this->actingAs($user)->patchJson('/api/v1/me', ['username' => 'hacker', 'email' => 'x@example.test'])->assertOk();
    expect($user->fresh())->username->toBe('maria')->email->toBe('maria@example.test');
});

it('needs a signed-in user to edit a profile', function () {
    $this->patchJson('/api/v1/me', ['name' => 'Х'])->assertUnauthorized();
});
