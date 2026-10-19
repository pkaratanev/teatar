<?php

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

function sentCode(User $user): string
{
    $code = null;
    Notification::assertSentTo($user, PasswordResetCode::class, function (PasswordResetCode $n) use (&$code) {
        $code = $n->code;

        return true;
    });

    return $code;
}

beforeEach(fn () => Notification::fake());

it('mails a code and resets the password with it', function () {
    $user = User::factory()->create(['email' => 'maria@example.test', 'password' => 'old-password']);
    $oldToken = $user->createToken('old phone')->plainTextToken;

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'maria@example.test'])->assertNoContent();
    $code = sentCode($user);
    expect($code)->toMatch('/^\d{6}$/');

    $token = $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'maria@example.test', 'code' => $code, 'password' => 'new-password', 'device_name' => 'android app',
    ])->assertOk()->assertJsonPath('user.email', 'maria@example.test')->json('token');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(1); // the old phone is signed out
    $this->withToken($token)->getJson('/api/v1/me')->assertOk();
    expect($oldToken)->not->toBe($token);

    // A code works once.
    $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'maria@example.test', 'code' => $code, 'password' => 'third-password', 'device_name' => 'x',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('answers the same for an unknown email and sends nothing', function () {
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.test'])->assertNoContent();

    Notification::assertNothingSent();
});

it('does not send a second code within a minute', function () {
    $user = User::factory()->create(['email' => 'a@example.test']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'a@example.test'])->assertNoContent();
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'a@example.test'])->assertNoContent();
    Notification::assertSentToTimes($user, PasswordResetCode::class, 1);

    $this->travel(2)->minutes();
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'a@example.test'])->assertNoContent();
    Notification::assertSentToTimes($user, PasswordResetCode::class, 2);
});

it('rejects a wrong code and an expired one', function () {
    $user = User::factory()->create(['email' => 'b@example.test', 'password' => 'old-password']);
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'b@example.test']);
    $code = sentCode($user);
    $wrong = $code === '000000' ? '111111' : '000000';

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'b@example.test', 'code' => $wrong, 'password' => 'new-password', 'device_name' => 'x',
    ])->assertJsonValidationErrors(['code' => 'Грешен или изтекъл код.']);

    $this->travel(31)->minutes();
    $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'b@example.test', 'code' => $code, 'password' => 'new-password', 'device_name' => 'x',
    ])->assertJsonValidationErrors('code');

    expect(Hash::check('old-password', $user->refresh()->password))->toBeTrue();
});

it('throws the code away after five wrong tries', function () {
    $user = User::factory()->create(['email' => 'c@example.test']);
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'c@example.test']);
    $code = sentCode($user);
    $wrong = $code === '000000' ? '111111' : '000000';
    $try = fn (string $c) => $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'c@example.test', 'code' => $c, 'password' => 'new-password', 'device_name' => 'x',
    ]);

    foreach (range(1, 5) as $i) {
        $try($wrong)->assertUnprocessable();
    }

    $try($code)->assertJsonValidationErrors(['code' => 'Твърде много опити. Поискайте нов код.']);
});
