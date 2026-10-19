<?php

use App\Adapters\ScrapedPerformance;
use App\Models\Production;
use App\Models\Theatre;
use App\Services\ProgrammeImporter;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

const POSTER = 'https://theatre.example/posters/gnyav.jpg';

function jpegBytes(): string
{
    $image = imagecreatetruecolor(60, 90);
    ob_start();
    imagejpeg($image);

    return (string) ob_get_clean();
}

function withPoster(?string $url = POSTER, string $title = 'Гняв'): ScrapedPerformance
{
    return new ScrapedPerformance(title: $title, startsAt: CarbonImmutable::now('Europe/Sofia')->addDays(3)->setTime(19, 0), posterUrl: $url);
}

beforeEach(function () {
    Storage::fake('public');
    $this->theatre = Theatre::factory()->create();
});

it('stores the theatre\'s poster once, with its source, and serves the card size', function () {
    Http::fake([POSTER => Http::response(jpegBytes(), 200, ['Content-Type' => 'image/jpeg'])]);

    $stats = app(ProgrammeImporter::class)->import($this->theatre, [withPoster(), withPoster()]);

    $production = Production::sole();
    expect($stats['posters'])->toBe(1)
        ->and($production->getFirstMedia('poster')->getCustomProperty('source_url'))->toBe(POSTER)
        ->and($production->posterUrl())->toContain('card');

    // The next sync finds the poster already there and downloads nothing.
    app(ProgrammeImporter::class)->import($this->theatre, [withPoster()]);
    Http::assertSentCount(1);
});

it('keeps a poster an admin uploaded', function () {
    $production = Production::factory()->for($this->theatre)->create(['title' => 'Гняв']);
    $production->addMedia(UploadedFile::fake()->image('admin.jpg', 60, 90))->toMediaCollection('poster');
    Http::fake();

    app(ProgrammeImporter::class)->import($this->theatre, [withPoster()]);

    Http::assertNothingSent();
    expect($production->fresh()->getFirstMedia('poster')->file_name)->toBe('admin.jpg');
});

it('skips a link that isn\'t an image or fails, and still imports the performance', function (int $status, string $type) {
    Http::fake([POSTER => Http::response('<html>Not found</html>', $status, ['Content-Type' => $type])]);

    $stats = app(ProgrammeImporter::class)->import($this->theatre, [withPoster()]);

    expect($stats)->found->toBe(1)->posters->toBe(0)
        ->and(Production::sole()->hasMedia('poster'))->toBeFalse();
})->with([[404, 'text/html'], [200, 'text/html']]);
