<?php

use App\Adapters\SofiaTheatreAdapter;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Theatre;
use Illuminate\Support\Facades\Http;

function sofiaFixture(): string
{
    return file_get_contents(base_path('tests/Fixtures/sofia/program-2026-11.html'));
}

it('parses rows with a time and skips sold-out rows', function () {
    $rows = collect((new SofiaTheatreAdapter)->parse(sofiaFixture(), 2026, 11));

    expect($rows->pluck('title')->all())->toBe(['УЖАСНИТЕ РОДИТЕЛИ', 'Феята от захарницата', 'УРОЦИ']);
});

it('takes month and year from the request and the day from the row', function () {
    $first = (new SofiaTheatreAdapter)->parse(sofiaFixture(), 2026, 11)[0];

    expect($first->startsAt->format('Y-m-d H:i'))->toBe('2026-11-10 19:00')
        ->and($first->stage)->toBe('Камерна сцена')
        ->and($first->author)->toBe('Жан Кокто')
        ->and($first->ticketUrl)->toBe('https://sofiatheatre.eu/program/838')
        ->and($first->sourceUrl)->toBe('https://sofiatheatre.eu/repertoires/264/uzasnite-roditeli');
});

it('keeps another venue as the stage, tidied', function () {
    $fairy = collect((new SofiaTheatreAdapter)->parse(sofiaFixture(), 2026, 11))->firstWhere('title', 'Феята от захарницата');

    expect($fairy->stage)->toBe('Театър „Сълза и смях“')
        ->and($fairy->startsAt->format('H:i'))->toBe('11:00');
});

it('refuses a page that shows a different month', function () {
    (new SofiaTheatreAdapter)->parse(sofiaFixture(), 2026, 12);
})->throws(RuntimeException::class, 'but the page shows');

it('returns nothing for an unpublished month', function () {
    $html = '<html><body><h3>Няма намерени актьори или представления</h3></body></html>';

    expect((new SofiaTheatreAdapter)->parse($html, 2026, 12))->toBe([]);
});

it('syncs end to end', function () {
    $this->travelTo(now('Europe/Sofia')->setDate(2026, 11, 1));
    Http::fake([
        'sofiatheatre.eu/program?year=2026&month=11' => Http::response(sofiaFixture()),
        'sofiatheatre.eu/*' => Http::response('<h3>Няма намерени актьори или представления</h3>'),
    ]);

    $theatre = Theatre::factory()->create(['adapter' => 'sofia', 'website' => 'https://sofiatheatre.eu']);

    SyncTheatreProgramme::dispatchSync($theatre);

    expect(AdapterRun::sole())->error->toBeNull()->performances_found->toBe(3)
        ->and($theatre->stages()->pluck('name')->sort()->values()->all())->toBe(['Камерна сцена', 'Театър „Сълза и смях“']);
});
