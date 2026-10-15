<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PerformanceController;
use App\Http\Controllers\Api\V1\PersonController;
use App\Http\Controllers\Api\V1\ProductionController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\TheatreController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Catalogue: public, no account needed to browse.
    Route::get('performances', [PerformanceController::class, 'index']);
    Route::get('productions/{production}', [ProductionController::class, 'show']);
    Route::get('theatres', [TheatreController::class, 'index']);
    Route::get('theatres/{theatre}', [TheatreController::class, 'show']);
    Route::get('people/{person}', [PersonController::class, 'show']);
    Route::get('search', SearchController::class);

    // Accounts.
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::delete('me', [AuthController::class, 'destroy']); // GDPR: delete account and all activity
    });
});
