<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Email + password accounts with Sanctum tokens. Sign in with Apple and
 * Google arrive in the "Accounts" phase: the app gets the provider token
 * natively and posts it to auth/social, which verifies it with Socialite.
 */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-z0-9_.]+$/', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create($data);
        $request->setUserResolver(fn () => $user); // so the response includes the user's own email

        return response()->json([
            'token' => $user->createToken($data['device_name'])->plainTextToken,
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Грешен имейл или парола.']);
        }
        $request->setUserResolver(fn () => $user); // so the response includes the user's own email

        return response()->json([
            'token' => $user->createToken($data['device_name'])->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /** Profile editing: display name and bio. Username and email stay fixed for now. */
    public function update(Request $request): UserResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if (array_key_exists('name', $data)) {
            $data['name'] = trim($data['name']);
        }
        if (array_key_exists('bio', $data)) {
            $data['bio'] = filled($data['bio']) ? trim($data['bio']) : null;
        }

        $user = $request->user();
        $user->update($data);

        return new UserResource($user);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /** The app registers its Expo push token after the user allows notifications; null turns pushes off. */
    public function pushToken(Request $request): Response
    {
        $data = $request->validate(['token' => ['nullable', 'string', 'max:255', 'regex:/^(Exponent|Expo)PushToken\[[^\]]+\]$/']]);
        $request->user()->forceFill(['expo_push_token' => $data['token'] ?? null])->save();

        return response()->noContent();
    }

    /** GDPR: deleting the account cascades to ratings, reviews, diary, watchlist and follows. */
    public function destroy(Request $request): Response
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }
}
