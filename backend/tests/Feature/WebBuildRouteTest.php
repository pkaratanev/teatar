<?php

use Illuminate\Support\Facades\File;

afterEach(fn () => File::deleteDirectory(public_path('app')));

it('serves the local web build for any /app path so deep links work', function () {
    File::ensureDirectoryExists(public_path('app'));
    File::put(public_path('app/index.html'), '<!doctype html><title>Teatar</title>');

    $this->get('/app/sign-in')->assertOk()->assertSee('<title>Teatar</title>', false);
    $this->get('/app/production/45')->assertOk();
});

it('answers 404 when no web build exists', function () {
    $this->get('/app/sign-in')->assertNotFound();
});
