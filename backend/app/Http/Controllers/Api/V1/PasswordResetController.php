<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Forgotten password, without links: the app asks for the email, we mail a
 * 6-digit code, and the app sends the code with the new password. A code lives
 * 30 minutes and allows 5 wrong tries; then a new one has to be requested.
 * Codes are stored hashed in password_reset_tokens.
 */
class PasswordResetController extends Controller
{
    public const CODE_MINUTES = 30;

    public const MAX_TRIES = 5;

    /** Always 204, so the screen can't be used to find out who has an account. */
    public function send(Request $request): Response
    {
        $email = $request->validate(['email' => ['required', 'email', 'max:255']])['email'];
        $user = User::where('email', $email)->first();

        $existing = DB::table('password_reset_tokens')->where('email', $email)->first();
        $justSent = $existing !== null && Carbon::parse($existing->created_at)->gt(now()->subMinute());

        if ($user !== null && ! $justSent) {
            $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($code), 'created_at' => now()],
            );
            RateLimiter::clear($this->triesKey($email));
            $user->notify(new PasswordResetCode($code));
        }

        return response()->noContent();
    }

    /** Sets the new password, signs out every device, and signs this one in. */
    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', Password::min(8)],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $key = $this->triesKey($data['email']);
        $row = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (RateLimiter::tooManyAttempts($key, self::MAX_TRIES)) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
            throw ValidationException::withMessages(['code' => 'Твърде много опити. Поискайте нов код.']);
        }

        $valid = $row !== null
            && Carbon::parse($row->created_at)->gt(now()->subMinutes(self::CODE_MINUTES))
            && Hash::check($data['code'], $row->token);
        $user = $valid ? User::where('email', $data['email'])->first() : null;

        if ($user === null) {
            RateLimiter::hit($key, self::CODE_MINUTES * 60);
            throw ValidationException::withMessages(['code' => 'Грешен или изтекъл код.']);
        }

        $user->password = $data['password'];
        $user->save();
        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
        RateLimiter::clear($key);

        return response()->json([
            'token' => $user->createToken($data['device_name'])->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    private function triesKey(string $email): string
    {
        return 'password-reset:'.sha1(mb_strtolower($email));
    }
}
