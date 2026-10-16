<?php

namespace App\Providers;

use App\Models\Person;
use App\Models\Production;
use App\Models\Theatre;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        // Short, stable names in polymorphic columns (follows, media) instead of class names.
        Relation::morphMap([
            'user' => User::class,
            'person' => Person::class,
            'production' => Production::class,
            'theatre' => Theatre::class,
        ]);

        // Reviews: generous for people, tight for brand-new accounts (review spam).
        RateLimiter::for('reviews', function (Request $request) {
            $user = $request->user();
            $newAccount = $user?->created_at?->gt(now()->subDay()) ?? true;

            return Limit::perHour($newAccount ? 10 : 60)->by($user?->id ?: $request->ip());
        });
    }
}
