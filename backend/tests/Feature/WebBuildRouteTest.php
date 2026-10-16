<?php

use Illuminate\Support\Facades\File;

// Never touch a real web build on a developer's PC (npm run web:local): these tests only run without one.
beforeEach(function () {
    if (File::exists(public_path('app'))) {
        $this->markTestSkipped('public/app holds a local web build');
    }
});

it('serves the local web build for any /app path so deep links work', function () {
    File::ensureDirectoryExists(public_path('app'));
    File::put(public_path('app/index.html'), '<!doctype html><title>Teatar</title>');

    try {
        $this->get('/app/sign-in')->assertOk()->assertSee('<title>Teatar</title>', false);
        $this->get('/app/production/45')->assertOk();
    } finally {
        File::deleteDirectory(public_path('app'));
    }
});

it('answers 404 when no web build exists', function () {
    $this->get('/app/sign-in')->assertNotFound();
});
