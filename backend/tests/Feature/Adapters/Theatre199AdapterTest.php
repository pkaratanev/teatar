<?php

use App\Adapters\Theatre199Adapter;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Theatre;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

function theatre199Fixture(): string
{
    return file_get_contents(base_path('tests/Fixtures/theatre199/schedule.html'));
}

it('reads the featured block and the cards, inferring the year', function () {
    $rows = collect((new Theatre199Adapter)->parse(theatre199Fixture(), CarbonImmutable::parse('2026-10-15', 'Europe/Sofia')));

    expect($rows->map(fn ($p) => $p->startsAt->format('Y-m-d H:i').' '.$p->title)->all())->toBe([
        '2026-10-15 19:30 ПРИЗЕМЕНА',
        '2026-10-25 19:30 ГОДИНАТА НА МАГИЧЕСКОТО МИСЛЕНЕ',
        '2026-10-31 11:30 ХРАБРИЯТ ШИВАЧ',
        '2026-11-01 15:00 ВЕЧЕРЕН АКТ',
    ]);

    expect($rows->first()->sourceUrl)->toBe('https://theatre199.org/bg/plays/p/654-prizemena-theatre199')
        ->and($rows->first()->stage)->toBeNull();
});

it('rolls dates into next year when they are past', function () {
    // Seen in December, "05.01" means next January.
    $html = '<main id="main"><div class="card-list"><span class="date">05.01</span><span class="list-time">19:30</span>'
        .'<h4><a href="https://theatre199.org/bg/plays/p/1-x">ТЕСТ</a></h4></div></main>';

    $row = (new Theatre199Adapter)->parse($html, CarbonImmutable::parse('2026-12-20', 'Europe/Sofia'))[0];

    expect($row->startsAt->format('Y-m-d'))->toBe('2027-01-05');
});

it('fails loudly when the layout changes', function () {
    (new Theatre199Adapter)->parse('<html><body><p>Нова визия</p></body></html>', CarbonImmutable::now());
})->throws(RuntimeException::class, 'layout may have changed');

it('pages until an empty page and syncs end to end', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00', 'Europe/Sofia'));
    Http::fake([
        'theatre199.org/bg/schedule?page=1&type=' => Http::response(theatre199Fixture()),
        'theatre199.org/bg/schedule?page=2&type=' => Http::response('<main id="main"><p>Няма</p></main>'),
    ]);

    $theatre = Theatre::factory()->create(['adapter' => 'theatre199', 'website' => 'https://theatre199.org']);

    SyncTheatreProgramme::dispatchSync($theatre);

    expect(AdapterRun::sole())->error->toBeNull()->performances_found->toBe(4);
    Http::assertSentCount(2);
});
