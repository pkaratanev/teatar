<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * One HTTP client for every theatre adapter: identifies us honestly
 * (config teatar.user_agent), asks for Bulgarian HTML, and retries a flaky
 * site once. It never tries to get around bot checks.
 */
final class TheatreHttp
{
    public static function client(): PendingRequest
    {
        return Http::withUserAgent((string) config('teatar.user_agent'))
            ->accept('text/html,application/xhtml+xml')
            ->withHeaders(['Accept-Language' => 'bg,en;q=0.5'])
            ->timeout(20)
            ->retry(2, 1500, throw: false);
    }
}
