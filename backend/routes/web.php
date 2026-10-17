<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Linked from Google Play and the app: the privacy policy and how to delete an account without the app.
Route::get('/privacy', fn () => view('legal.privacy', [
    'contact' => config('teatar.contact_email'),
    'updated' => '17.10.2026',
]))->name('privacy');
Route::get('/delete-account', fn () => view('legal.delete-account', [
    'contact' => config('teatar.contact_email'),
]))->name('delete-account');

// The app's web build for testing in a browser on this PC (`npm run web:local` in mobile/ builds it into
// public/app-build). Files are served from there; any other /app path gets index.html and the app's own router
// takes over, so deep links work.
Route::get('/app/{path?}', function (?string $path = null) {
    $root = realpath(public_path('app-build'));
    abort_unless($root !== false && is_file("{$root}/index.html"), 404, 'No web build yet: run "npm run web:local" in mobile/.');

    $file = $path === null ? false : realpath("{$root}/{$path}");
    if ($file === false || ! is_file($file) || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR)) {
        return response()->file("{$root}/index.html", ['Content-Type' => 'text/html; charset=utf-8']);
    }

    $types = [
        'js' => 'application/javascript', 'css' => 'text/css', 'json' => 'application/json', 'map' => 'application/json',
        'html' => 'text/html; charset=utf-8', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon', 'ttf' => 'font/ttf', 'otf' => 'font/otf', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
    ];
    $type = $types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? null;

    return response()->file($file, $type ? ['Content-Type' => $type] : []);
})->where('path', '.*');
