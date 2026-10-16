<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Reviews: generous for people, tight for brand-new accounts (review spam).
        RateLimiter::for('reviews', function (Request $request) {
            $user = $request->user();
            $newAccount = $user?->created_at?->gt(now()->subDay()) ?? true;

            return Limit::perHour($newAccount ? 10 : 60)->by($user?->id ?: $request->ip());
        });
    }
}
