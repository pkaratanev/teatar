<?php

use App\Adapters\TheatroAdapter;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Production;
use App\Models\Theatre;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

function theatroFixture(): string
{
    return file_get_contents(base_path('tests/Fixtures/theatro/program.html'));
}

function theatroRows(): Illuminate\Support\Collection
{
    return collect((new TheatroAdapter)->parse(theatroFixture(), CarbonImmutable::parse('2026-10-15', 'Europe/Sofia')));
}

it('skips hidden promo rows, headings and cancellation notices', function () {
    expect(theatroRows()->pluck('title')->all())->toBe([
        'Илюзии', 'Трудни за обичане', 'Théatro AURA', 'Сватба със закъснител',
    ]);
});

it('infers the year and reads time and hall', function () {
    $rows = theatroRows();

    expect($rows->firstWhere('title', 'Илюзии')->startsAt->format('Y-m-d H:i'))->toBe('2026-10-16 19:30')
        ->and($rows->firstWhere('title', 'Сватба със закъснител')->startsAt->format('Y-m-d H:i'))->toBe('2026-11-02 19:30')
        // "Голяма Зала" and "Голяма зала" are the same hall.
        ->and($rows->pluck('stage')->unique()->values()->all())->toBe(['Голяма зала']);
});

it('reads guest company, author, ticket and production links', function () {
    $rows = theatroRows();

    $guest = $rows->firstWhere('title', 'Трудни за обичане');
    expect($guest->visitingCompany)->toBe('ДТ „Рачо Стоянов“ – Габрово')
        ->and($guest->author)->toBe('по Леополд Герш')
        ->and($guest->ticketUrl)->toBe('https://entase.to/04f');

    $premiere = $rows->firstWhere('title', 'Илюзии');
    expect($premiere->author)->toBe('Иван Вирипаев')
        ->and($premiere->guest())->toBeFalse()
        ->and($premiere->ticketUrl)->toStartWith('https://www.eventim.bg/event/')
        ->and($premiere->sourceUrl)->toBe('https://www.theatro.bg/portfolio_page/илюзии/');

    // No production page link: fall back to the programme page.
    expect($rows->firstWhere('title', 'Théatro AURA')->sourceUrl)->toBe('https://www.theatro.bg/program/');
});

it('splits titles with odd quotes and trailing author notes', function (string $line, string $title, ?string $author) {
    expect(TheatroAdapter::splitTitle($line))->toBe([$title, $author]);
})->with([
    ['„Червена звезда бл.28″', 'Червена звезда бл.28', null],
    ['„Трудни за обичане“ по Леополд Герш', 'Трудни за обичане', 'по Леополд Герш'],
    ['Théatro AURA', 'Théatro AURA', null],
]);

it('syncs end to end', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00', 'Europe/Sofia'));
    Http::fake(['www.theatro.bg/program/' => Http::response(theatroFixture())]);

    $theatre = Theatre::factory()->create(['adapter' => 'theatro', 'website' => 'https://www.theatro.bg']);

    SyncTheatreProgramme::dispatchSync($theatre);

    expect(AdapterRun::sole())->error->toBeNull()->performances_found->toBe(4)->productions_created->toBe(4)
        ->and(Production::where('title', 'Трудни за обичане')->sole())
        ->is_guest->toBeTrue()
        ->visiting_company->toBe('ДТ „Рачо Стоянов“ – Габрово');
});
