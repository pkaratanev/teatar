<?php

use App\Adapters\SatirataAdapter;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Production;
use App\Models\Theatre;
use Illuminate\Support\Facades\Http;

function satirataFixture(): string
{
    return file_get_contents(base_path('tests/Fixtures/satirata/program-2026-11.html'));
}

it('reads the month from the embedded Next.js data', function () {
    $rows = collect((new SatirataAdapter)->parse(satirataFixture(), 2026, 11));

    expect($rows->map(fn ($p) => $p->startsAt->format('Y-m-d H:i').' '.$p->title)->all())->toBe([
        '2026-11-01 11:30 Том Сойер и Хък Фин',
        '2026-11-01 17:30 Примадони',
        '2026-11-01 20:00 Жената пита ChatGPT',
        '2026-11-03 20:00 Да останем само приятели',
        '2026-11-21 19:30 Луди за връзване',
    ]);
});

it('maps stages, prices in euro, tickets, age rating and production page', function () {
    $row = collect((new SatirataAdapter)->parse(satirataFixture(), 2026, 11))->firstWhere('title', 'Луди за връзване');

    expect($row)
        ->stage->toBe('Голяма сцена')
        ->priceFrom->toBe(18.0)
        ->priceTo->toBe(30.0)
        ->ticketUrl->toBe('https://epaygo.bg/4144143049')
        ->ageAdvisory->toBe('16+')
        ->sourceUrl->toBe('https://satirata.bg/show/ludi-za-vrazvane');
});

it('keeps only the month asked for', function () {
    expect((new SatirataAdapter)->parse(satirataFixture(), 2026, 12))->toBe([]);
});

it('fails loudly when the data is missing', function () {
    (new SatirataAdapter)->parse('<html><body>Нова визия</body></html>', 2026, 11);
})->throws(RuntimeException::class, 'layout may have changed');

it('syncs end to end and stores the age rating', function () {
    $this->travelTo(now('Europe/Sofia')->setDate(2026, 11, 1));
    Http::fake(['satirata.bg/*' => Http::response(satirataFixture())]);

    $theatre = Theatre::factory()->create(['adapter' => 'satirata', 'website' => 'https://satirata.bg']);

    SyncTheatreProgramme::dispatchSync($theatre);

    expect(AdapterRun::sole())->error->toBeNull()->performances_found->toBe(5)
        ->and(Production::where('title', 'Луди за връзване')->sole()->age_advisory)->toBe('16+')
        ->and($theatre->stages()->count())->toBe(3);
});
