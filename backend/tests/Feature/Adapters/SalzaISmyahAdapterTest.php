<?php

use App\Adapters\SalzaISmyahAdapter;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Theatre;
use Illuminate\Support\Facades\Http;

function salzaFixture(string $name): string
{
    return file_get_contents(base_path("tests/Fixtures/salzaismyah/{$name}.html"));
}

function salzaRows(): Illuminate\Support\Collection
{
    $adapter = new SalzaISmyahAdapter;
    $index = $adapter->indexStageLists([1 => salzaFixture('events-1'), 2 => salzaFixture('events-2')]);

    return collect($adapter->parseCalendar(salzaFixture('calendar-2026-11'), 2026, 11, $index));
}

it('reads every event in the month and skips padding days', function () {
    expect(salzaRows()->map(fn ($p) => $p->startsAt->format('d H:i').' '.$p->title)->all())->toBe([
        '01 19:00 ЖЕНА МИ СЕ КАЗВА БОРИС',
        '03 19:00 БАЩА МИ СЕ КАЗВА МАРИЯ - НА СЕЛО',
        '03 19:30 Как се търси нов съпруг',
        '14 11:00 ФЕЯТА ОТ ЗАХАРНИЦАТА',
        '14 19:00 АЗ ДОСАДНИКЪТ',
        '16 19:00 ПЪТУВАНЕ ДО МАРС',
        '16 19:30 СНАХА',
    ]);
});

it('joins stage and production page from the stage lists by ticket id', function () {
    $rows = salzaRows();

    expect($rows->firstWhere('title', 'СНАХА'))
        ->stage->toBe('Камерна сцена „Славянска беседа“')
        ->sourceUrl->toBe('https://www.salzaismyah.bg/site/play/738')
        ->ticketUrl->toBe('https://www.salzaismyah.bg/site/selector/3188');

    expect($rows->firstWhere('title', 'ФЕЯТА ОТ ЗАХАРНИЦАТА')->stage)->toBe('Открита сцена');

    // Not in any stage list: no stage, programme page as the source.
    expect($rows->firstWhere('title', 'ЖЕНА МИ СЕ КАЗВА БОРИС'))
        ->stage->toBeNull()
        ->sourceUrl->toBe('https://www.salzaismyah.bg/site/calendar/2026/11');
});

it('refuses a calendar for a different month', function () {
    (new SalzaISmyahAdapter)->parseCalendar(salzaFixture('calendar-2026-11'), 2026, 12);
})->throws(RuntimeException::class, 'but the page shows');

it('syncs end to end', function () {
    $this->travelTo(now('Europe/Sofia')->setDate(2026, 11, 1));
    $empty = fn (string $month) => '<div class="calendar"><div class="row mb-2"><span class="h3">'.$month.'</span></div></div>';
    Http::fake([
        'www.salzaismyah.bg/site/events/1' => Http::response(salzaFixture('events-1')),
        'www.salzaismyah.bg/site/events/2' => Http::response(salzaFixture('events-2')),
        'www.salzaismyah.bg/site/events/3' => Http::response('<h4>Няма намерени представления.</h4>'),
        'www.salzaismyah.bg/site/calendar/2026/11' => Http::response(salzaFixture('calendar-2026-11')),
        'www.salzaismyah.bg/site/calendar/2026/12' => Http::response($empty('Декември 2026')),
        'www.salzaismyah.bg/site/calendar/2027/01' => Http::response($empty('Януари 2027')),
    ]);

    $theatre = Theatre::factory()->create(['adapter' => 'salzaismyah', 'website' => 'https://www.salzaismyah.bg']);

    SyncTheatreProgramme::dispatchSync($theatre);

    expect(AdapterRun::sole())->error->toBeNull()->performances_found->toBe(7)
        ->and($theatre->stages()->count())->toBe(2);
});
