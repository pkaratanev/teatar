<?php

use App\Adapters\ZadKanalaAdapter;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Performance;
use App\Models\Theatre;
use Illuminate\Support\Facades\Http;

function zadKanalaFixture(): string
{
    return file_get_contents(base_path('tests/Fixtures/zadkanala/programa-2026-11.html'));
}

it('parses the calendar rows with their machine-readable dates', function () {
    $rows = collect((new ZadKanalaAdapter)->parse(zadKanalaFixture()));

    expect($rows->pluck('title')->all())->toBe([
        'Недоразбраната цивилизация /или загадката на пирамидите/', 'СЪН В ЗИМНА НОЩ', 'ВЕЛИКА',
        'Поп - фолк хроники: Бели птици и куршуми', 'ВЕЛИКА',
    ]);

    $first = $rows->first();
    expect($first->startsAt->format('Y-m-d H:i'))->toBe('2026-11-01 19:00')
        ->and($first->startsAt->timezoneName)->toBe('Europe/Sofia')
        ->and($first->ticketUrl)->toBeNull()
        ->and($first->sourceUrl)->toBe('https://zadkanala.bg/spektakli/nedorazbranata-tzivilizatziya-ili-zagadkata-na-piramidite');
});

it('falls back to the printed date when the timestamp is missing', function () {
    $html = '<div class="view-calendar"><table><tbody><tr>'
        .'<td class="views-field-field-dates"><span class="date-display-single">2 Дек. 2026 - 19:30</span></td>'
        .'<td class="views-field-title"><a href="/spektakli/velika">ВЕЛИКА</a></td></tr></tbody></table></div>';

    expect((new ZadKanalaAdapter)->parse($html)[0]->startsAt->format('Y-m-d H:i'))->toBe('2026-12-02 19:30');
});

it('fails loudly when the layout changes', function () {
    (new ZadKanalaAdapter)->parse('<html><body><p>Нова визия</p></body></html>');
})->throws(RuntimeException::class, 'layout may have changed');

it('syncs end to end', function () {
    $this->travelTo(now('Europe/Sofia')->setDate(2026, 11, 1));
    Http::fake([
        'zadkanala.bg/programa/2026-11' => Http::response(zadKanalaFixture()),
        'zadkanala.bg/*' => Http::response('<div class="view view-calendar"></div>'),
    ]);

    $theatre = Theatre::factory()->create(['adapter' => 'zadkanala', 'website' => 'https://zadkanala.bg']);

    SyncTheatreProgramme::dispatchSync($theatre);

    expect(AdapterRun::sole())->error->toBeNull()->performances_found->toBe(5)->productions_created->toBe(4)
        ->and(Performance::count())->toBe(5)
        ->and($theatre->productions()->where('title', 'ВЕЛИКА')->sole()->performances()->count())->toBe(2);
});
