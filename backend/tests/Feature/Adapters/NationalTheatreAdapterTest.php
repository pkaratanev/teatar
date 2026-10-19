<?php

use App\Adapters\NationalTheatreAdapter;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Performance;
use App\Models\Production;
use App\Models\Theatre;
use Illuminate\Support\Facades\Http;

function nationalFixture(): string
{
    return file_get_contents(base_path('tests/Fixtures/national/programme-2026-11.html'));
}

it('parses every show and skips building tours', function () {
    $rows = collect((new NationalTheatreAdapter)->parse(nationalFixture(), 2026, 11));

    expect($rows->pluck('title')->all())->toBe([
        'ТЕАТЪР', 'Аз плащам', 'Малката Англия', 'Разходка с Гогол', 'Ария на съперницата',
        'НОРА | #СценаБезГраници', 'СВРЪХПРЕДЕЛ',
    ]);
});

it('reads date and time from the ticket link, stage and author from the listing', function () {
    $rows = collect((new NationalTheatreAdapter)->parse(nationalFixture(), 2026, 11));

    $aria = $rows->firstWhere('title', 'Ария на съперницата');
    expect($aria->startsAt->format('Y-m-d H:i'))->toBe('2026-11-05 19:30')
        ->and($aria->startsAt->timezoneName)->toBe('Europe/Sofia')
        ->and($aria->stage)->toBe('Сцена „Апостол Карамитев“')
        ->and($aria->author)->toBe('Ерик-Еманюел Шмит')
        ->and($aria->ticketUrl)->toBe('https://nationaltheatre.bg/bg/predstavlenie/ariya-na-spernicata/2026-11-05%7C19:30:00')
        ->and($aria->sourceUrl)->toBe('https://nationaltheatre.bg/bg/predstavlenie/ariya-na-spernicata');

    expect($rows->firstWhere('title', 'ТЕАТЪР')->posterUrl)->toBe('https://nationaltheatre.bg/storage/shows/29394ef0e98855188a3c47a6e5af7d2edb1.jpg');

    // "по …" author lines are kept as written.
    expect($rows->firstWhere('title', 'ТЕАТЪР')->author)->toBe('по едноименния роман на Съмърсет Моъм');
});

it('marks guest shows from the „Гостуващ“ label', function () {
    $rows = collect((new NationalTheatreAdapter)->parse(nationalFixture(), 2026, 11));

    $guests = $rows->filter->guest();
    expect($guests->pluck('title')->all())->toBe(['НОРА | #СценаБезГраници'])
        ->and($guests->first()->visitingCompany)->toBeNull();
});

it('refuses a page that shows a different month than asked for', function () {
    (new NationalTheatreAdapter)->parse(nationalFixture(), 2026, 12);
})->throws(RuntimeException::class, 'but the page shows');

it('fails loudly when the layout changes', function () {
    (new NationalTheatreAdapter)->parse('<html><body><p>Нова визия</p></body></html>', 2026, 11);
})->throws(RuntimeException::class, 'layout may have changed');

it('syncs end to end and fills a missing author', function () {
    $this->travelTo(now('Europe/Sofia')->setDate(2026, 11, 1));

    Http::fake([
        'nationaltheatre.bg/bg/programa?day=&month=11&year=2026' => Http::response(nationalFixture()),
        'nationaltheatre.bg/*' => fn ($request) => Http::response(
            '<section class="calendar"><div class="month"><div class="month-title"><h2>'
            .(str_contains($request->url(), 'month=12') ? 'декември' : 'януари')
            .' <span>'.(str_contains($request->url(), 'month=12') ? '2026' : '2027').'</span></h2></div><div class="items"></div></div></section>'
        ),
    ]);

    $theatre = Theatre::factory()->create(['adapter' => 'national', 'website' => 'https://nationaltheatre.bg']);
    $existing = Production::factory()->for($theatre)->create(['title' => 'Аз плащам', 'author' => null]);

    SyncTheatreProgramme::dispatchSync($theatre);

    expect(AdapterRun::sole())
        ->error->toBeNull()
        ->status->toBe('succeeded')
        ->performances_found->toBe(7)
        ->productions_created->toBe(6);

    expect($existing->fresh()->author)->toBe('Ив Жамиак')
        ->and(Production::where('title', 'НОРА | #СценаБезГраници')->sole()->is_guest)->toBeTrue()
        ->and(Performance::count())->toBe(7)
        ->and($theatre->stages()->count())->toBe(3);
});
