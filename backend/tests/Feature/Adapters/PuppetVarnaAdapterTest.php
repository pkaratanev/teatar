<?php

use App\Adapters\PuppetVarnaAdapter;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Performance;
use App\Models\Theatre;
use Illuminate\Support\Facades\Http;

function puppetVarnaFixture(): string
{
    return file_get_contents(base_path('tests/Fixtures/vnpuppet/events.json'));
}

it('reads the Bulgarian events from the feed and skips the English copies', function () {
    $rows = collect((new PuppetVarnaAdapter)->parse(puppetVarnaFixture()));

    expect($rows->pluck('title')->all())->toBe(['Мечо Пух', 'Мечо Пух', 'Пепеляшка', 'Пепеляшка', 'Лазарица']);

    $first = $rows->first();
    expect($first->startsAt->format('Y-m-d H:i'))->toBe('2026-10-16 18:00')
        ->and($first->startsAt->timezoneName)->toBe('Europe/Sofia')
        ->and($first->sourceUrl)->toStartWith('https://vnpuppet.com/event/')
        ->and($first->guest())->toBeFalse();
});

it('reads a guest company from the title', function () {
    $guest = collect((new PuppetVarnaAdapter)->parse(puppetVarnaFixture()))->last();

    expect($guest->title)->toBe('Лазарица')
        ->and($guest->visitingCompany)->toBe('ДТ-Русе')
        ->and($guest->startsAt->format('Y-m-d H:i'))->toBe('2026-11-03 19:00');
});

it('fails loudly when the feed changes', function () {
    (new PuppetVarnaAdapter)->parse('{"posts": []}');
})->throws(RuntimeException::class, 'site may have changed');

it('syncs end to end with a single request', function () {
    $this->travelTo(now('Europe/Sofia')->setDate(2026, 10, 16)->setTime(10, 0));
    Http::fake(['vnpuppet.com/wp-json/tribe/events/v1/events*' => Http::response(puppetVarnaFixture())]);

    $theatre = Theatre::factory()->create(['adapter' => 'vnpuppet', 'website' => 'https://vnpuppet.com', 'city' => 'Варна']);

    SyncTheatreProgramme::dispatchSync($theatre);

    Http::assertSentCount(1);
    expect(AdapterRun::sole())->error->toBeNull()->performances_found->toBe(5)->productions_created->toBe(3)
        ->and(Performance::count())->toBe(5)
        ->and($theatre->productions()->where('title', 'Лазарица')->sole())->is_guest->toBeTrue();
});
