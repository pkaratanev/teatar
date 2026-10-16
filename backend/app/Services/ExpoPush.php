<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications through Expo's push service, which delivers to
 * APNs (iOS) and FCM (Android). Tokens look like "ExponentPushToken[...]".
 */
class ExpoPush
{
    public const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /**
     * @param  array<string, mixed>  $data  opened by the app when the notification is tapped
     * @return bool  whether Expo accepted the message
     */
    public function send(string $token, string $title, string $body, array $data = []): bool
    {
        if (! self::isValidToken($token)) {
            return false;
        }

        $response = Http::acceptJson()->timeout(10)->post(self::ENDPOINT, [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'data' => (object) $data,
            'sound' => 'default',
        ]);

        $ok = $response->successful() && $response->json('data.status') === 'ok';
        if (! $ok) {
            Log::warning('Expo push failed', ['status' => $response->status(), 'body' => $response->json()]);
        }

        return $ok;
    }

    public static function isValidToken(?string $token): bool
    {
        return is_string($token) && (bool) preg_match('/^(Exponent|Expo)PushToken\[[^\]]+\]$/', $token);
    }
}
