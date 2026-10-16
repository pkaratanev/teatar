<?php

use Illuminate\Support\Facades\File;

// Never touch a real web build on a developer's PC (npm run web:local): these tests only run without one.
beforeEach(function () {
    if (File::exists(public_path('app-build'))) {
        $this->markTestSkipped('public/app-build holds a local web build');
    }
});

function fakeWebBuild(): void
{
    File::ensureDirectoryExists(public_path('app-build/_expo/static/js'));
    File::put(public_path('app-build/index.html'), '<!doctype html><title>Teatar</title>');
    File::put(public_path('app-build/_expo/static/js/entry.js'), 'console.log(1)');
}

it('serves the web build at /app, its files, and index.html for any other path', function () {
    fakeWebBuild();

    try {
        foreach (['/app', '/app/', '/app/sign-in', '/app/production/45'] as $path) {
            $response = $this->get($path)->assertOk();
            expect($response->baseResponse->getFile()->getFilename())->toBe('index.html');
        }

        $js = $this->get('/app/_expo/static/js/entry.js')->assertOk();
        expect($js->headers->get('Content-Type'))->toBe('application/javascript')
            ->and($js->baseResponse->getFile()->getFilename())->toBe('entry.js');

        // Nothing outside the build folder.
        expect($this->get('/app/..%2F..%2F.env')->baseResponse->getFile()->getFilename())->toBe('index.html');
    } finally {
        File::deleteDirectory(public_path('app-build'));
    }
});

it('answers 404 when no web build exists', function () {
    $this->get('/app/sign-in')->assertNotFound();
});
