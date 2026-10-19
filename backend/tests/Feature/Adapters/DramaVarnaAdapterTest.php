<?php

use App\Adapters\DramaVarnaAdapter;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Performance;
use App\Models\Theatre;
use Illuminate\Support\Facades\Http;

function dramaVarnaFixture(string $name = 'program-2026-11.html'): string
{
    return file_get_contents(base_path("tests/Fixtures/dramavarna/{$name}"));
}

it('parses each performance of the month with stage, credits and a ticket link', function () {
    $rows = collect((new DramaVarnaAdapter)->parse(dramaVarnaFixture(), 2026, 11));

    expect($rows)->toHaveCount(19);

    $first = $rows->first();
    expect($first->title)->toBe('ЗАЩОТО В ТВОИТЕ ОЧИ...')
        ->and($first->startsAt->format('Y-m-d H:i'))->toBe('2026-11-02 19:00')
        ->and($first->startsAt->timezoneName)->toBe('Europe/Sofia')
        ->and($first->stage)->toBe('Сцена Филиал')
        ->and($first->author)->toBe('по романа на Борислав Вълов – Ей Бо')
        ->and($first->director)->toBe('Стоян Радев Ге. К.')
        ->and($first->sourceUrl)->toBe('https://dramavarna.com/index.php/bg/program-and-tickets?event=953')
        ->and($first->ticketUrl)->toStartWith('https://fccvarna.bg/')
        ->and($first->posterUrl)->toBe('https://dramavarna.com/images/operaevents/953_509_zashtoto_v_tvoite_ochi_poster.jpg');

    // Three Пепеляшка shows on one day, each with its own time.
    expect($rows->where('title', 'ИСТОРИЯТА НА ПЕПЕЛЯШКА')->map(fn ($r) => $r->startsAt->format('d H:i'))->values()->all())
        ->toBe(['26 11:00', '26 14:00', '26 18:00']);

    // A ticket href with junk in front of the URL; a show with no ticket links yet.
    expect($rows->firstWhere('title', 'АЛМА МАЛЕР')->ticketUrl)->toStartWith('https://fccvarna.bg/')
        ->and($rows->firstWhere('title', 'ИЗОБРЕТАТЕЛНАТА ВЛЮБЕНА'))
        ->author->toBe('комедия от Лопе де Вега')
        ->director->toBe('Биляна Стоева')
        ->ticketUrl->toBeNull();
});

it('takes nothing from an empty month or from a page showing another month', function () {
    expect((new DramaVarnaAdapter)->parse(dramaVarnaFixture('program-2026-12-empty.html'), 2026, 12))->toBe([])
        ->and((new DramaVarnaAdapter)->parse(dramaVarnaFixture(), 2026, 12))->toBe([]);
});

it('fails loudly when the layout changes', function () {
    (new DramaVarnaAdapter)->parse('<html><body><p>Нова визия</p></body></html>');
})->throws(RuntimeException::class, 'layout may have changed');

it('syncs end to end', function () {
    $this->travelTo(now('Europe/Sofia')->setDate(2026, 11, 1)->setTime(10, 0));
    Http::fake([
        'dramavarna.com/index.php/bg/program-and-tickets?month=11&year=2026' => Http::response(dramaVarnaFixture()),
        'dramavarna.com/*' => Http::response(dramaVarnaFixture('program-2026-12-empty.html')),
    ]);

    $theatre = Theatre::factory()->create(['adapter' => 'dramavarna', 'website' => 'https://dramavarna.com', 'city' => 'Варна']);

    SyncTheatreProgramme::dispatchSync($theatre);

    expect(AdapterRun::sole())->error->toBeNull()->performances_found->toBe(19)
        ->and(Performance::count())->toBe(19)
        ->and($theatre->productions()->where('title', 'ИСТОРИЯТА НА ПЕПЕЛЯШКА')->sole()->performances()->count())->toBe(3);
});
