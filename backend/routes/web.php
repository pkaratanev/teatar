<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// The app's web build for testing in a browser on this PC (`npm run web:local` in mobile/). Files under
// public/app are served directly; every other /app path gets index.html so the app's own router handles it.
Route::get('/app/{path?}', function () {
    $index = public_path('app/index.html');
    abort_unless(is_file($index), 404, 'No web build yet: run "npm run web:local" in mobile/.');

    return response()->file($index);
})->where('path', '.*');
