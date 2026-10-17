<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CityController;
use App\Http\Controllers\Api\V1\PerformanceController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\PersonController;
use App\Http\Controllers\Api\V1\ProductionController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SocialController;
use App\Http\Controllers\Api\V1\TheatreController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Catalogue: public, no account needed to browse.
    Route::get('cities', [CityController::class, 'index']);
    Route::get('performances', [PerformanceController::class, 'index']);
    Route::get('productions/{production}', [ProductionController::class, 'show']);
    Route::get('theatres', [TheatreController::class, 'index']);
    Route::get('theatres/{theatre}', [TheatreController::class, 'show']);
    Route::get('people/{person}', [PersonController::class, 'show']);
    Route::get('search', SearchController::class);
    Route::get('productions/{production}/reviews', [ReviewController::class, 'index']);
    Route::get('users/{username}', [ProfileController::class, 'show']);

    // Accounts.
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/forgot-password', [PasswordResetController::class, 'send']);
        Route::post('auth/reset-password', [PasswordResetController::class, 'reset']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('me', [AuthController::class, 'update']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::delete('me', [AuthController::class, 'destroy']); // GDPR: delete account and all activity
        Route::put('me/push-token', [AuthController::class, 'pushToken']);

        // Your theatre life.
        Route::get('me/diary', [ProfileController::class, 'diary']);
        Route::get('me/watchlist', [ProfileController::class, 'watchlist']);
        Route::get('me/ratings', [ProfileController::class, 'ratings']);

        Route::prefix('productions/{production}')->controller(ActivityController::class)->group(function () {
            Route::put('rating', 'rate');
            Route::delete('rating', 'unrate');
            Route::put('review', 'review')->middleware('throttle:reviews');
            Route::delete('review', 'unreview');
            Route::post('diary', 'logVisit');
            Route::put('watchlist', 'watch');
            Route::delete('watchlist', 'unwatch');
            Route::post('log', 'log')->middleware('throttle:reviews');
        });
        Route::delete('diary/{entry}', [ActivityController::class, 'deleteVisit']);
        Route::post('reviews/{review}/report', [ReviewController::class, 'report'])->middleware('throttle:10,1');

        // Social.
        Route::controller(SocialController::class)->group(function () {
            Route::get('me/feed', 'feed');
            Route::get('me/following', 'following');
            Route::put('users/{username}/follow', 'followUser');
            Route::delete('users/{username}/follow', 'unfollowUser');
            Route::put('people/{person}/follow', 'followPerson');
            Route::delete('people/{person}/follow', 'unfollowPerson');
            Route::put('reviews/{review}/like', 'like');
            Route::delete('reviews/{review}/like', 'unlike');
        });
    });
});
