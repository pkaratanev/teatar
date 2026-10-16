<?php

use App\Adapters\ScrapedPerformance;
use App\Models\Performance;
use App\Models\Production;
use App\Models\Theatre;
use App\Services\ProgrammeImporter;
use Carbon\CarbonImmutable;

/** A show $days from now at $time (Sofia time), as an adapter would report it. */
function show(string $title, int $days, string $time = '19:00', ?string $stage = 'Голяма сцена', ?string $ticket = null): ScrapedPerformance
{
    [$h, $m] = explode(':', $time);
    $at = CarbonImmutable::now('Europe/Sofia')->addDays($days)->setTime((int) $h, (int) $m);

    return new ScrapedPerformance($title, $at, $stage, ticketUrl: $ticket);
}

function importShows(Theatre $theatre, array $rows): array
{
    return app(ProgrammeImporter::class)->import($theatre, $rows);
}

it('keeps the same row and id when a show moves to another time on the same day', function () {
    $theatre = Theatre::factory()->create();
    importShows($theatre, [show('Гняв', 5), show('Гняв', 6), show('Албион', 7)]);
    $moving = Performance::orderBy('starts_at')->first();

    $stats = importShows($theatre, [show('Гняв', 5, '19:30'), show('Гняв', 6), show('Албион', 7)]);

    expect($stats)->toMatchArray(['moved' => 1, 'cancelled' => 0])
        ->and(Performance::count())->toBe(3)
        ->and($moving->fresh()->starts_at->setTimezone('Europe/Sofia')->format('H:i'))->toBe('19:30')
        ->and($moving->fresh()->is_cancelled)->toBeFalse();
});

it('follows a per-show ticket link to another day', function () {
    $theatre = Theatre::factory()->create();
    importShows($theatre, [show('Албион', 1), show('Гняв', 5, ticket: 'https://entase.com/book?eid=1'), show('Гняв', 6, ticket: 'https://entase.com/book?eid=2')]);
    $moving = Performance::where('ticket_url', 'https://entase.com/book?eid=1')->sole();

    $stats = importShows($theatre, [show('Албион', 1), show('Гняв', 9, ticket: 'https://entase.com/book?eid=1'), show('Гняв', 6, ticket: 'https://entase.com/book?eid=2')]);

    expect($stats)->toMatchArray(['moved' => 1, 'cancelled' => 0])
        ->and(Performance::count())->toBe(3)
        ->and($moving->fresh()->starts_at->isSameDay(CarbonImmutable::now()->addDays(9)))->toBeTrue();
});

it('cancels a show the source no longer lists', function () {
    $theatre = Theatre::factory()->create();
    importShows($theatre, [show('Гняв', 5), show('Албион', 6), show('Гняв', 7)]);

    $stats = importShows($theatre, [show('Гняв', 5), show('Гняв', 7)]);

    expect($stats)->toMatchArray(['moved' => 0, 'cancelled' => 1])
        ->and(Performance::where('is_cancelled', true)->sole()->production->title)->toBe('Албион');
});

it('cancels and adds when a move is ambiguous', function () {
    $theatre = Theatre::factory()->create();
    // Two shows on the same day, both move: can't tell which became which.
    importShows($theatre, [show('Гняв', 5, '15:00'), show('Гняв', 5, '19:00'), show('Гняв', 8)]);

    $stats = importShows($theatre, [show('Гняв', 5, '16:00'), show('Гняв', 5, '20:00'), show('Гняв', 8)]);

    expect($stats)->toMatchArray(['moved' => 0, 'cancelled' => 2])
        ->and(Performance::where('is_cancelled', false)->count())->toBe(3);
});

it('only touches the days the source covered', function () {
    $theatre = Theatre::factory()->create();
    importShows($theatre, [show('Гняв', 5), show('Гняв', 40)]);

    // This run only reached the next month; the show in 40 days is outside it.
    $stats = importShows($theatre, [show('Гняв', 5), show('Албион', 20)]);

    expect($stats['cancelled'])->toBe(0)
        ->and(Performance::where('is_cancelled', true)->count())->toBe(0);
});

it('never cancels admin-entered performances or past ones', function () {
    $theatre = Theatre::factory()->create();
    $production = Production::factory()->for($theatre)->create(['title' => 'Гняв']);
    $admin = Performance::factory()->for($production)->create(['starts_at' => now()->addDays(6), 'source' => 'admin']);
    $past = Performance::factory()->for($production)->create(['starts_at' => now()->subDay(), 'source' => 'adapter']);

    importShows($theatre, [show('Гняв', -2), show('Гняв', 5), show('Гняв', 8)]);

    expect($admin->fresh()->is_cancelled)->toBeFalse()
        ->and($past->fresh()->is_cancelled)->toBeFalse();
});

it('holds back a mass cancellation that looks like a broken page', function () {
    $theatre = Theatre::factory()->create();
    importShows($theatre, collect(range(1, 10))->map(fn ($d) => show('Гняв', $d))->all());

    $stats = importShows($theatre, [show('Гняв', 1), show('Гняв', 10)]);

    expect($stats)->toMatchArray(['cancelled' => 0, 'held_back' => 8])
        ->and(Performance::where('is_cancelled', true)->count())->toBe(0);
});

it('lists a cancelled show again when the source brings it back', function () {
    $theatre = Theatre::factory()->create();
    importShows($theatre, [show('Гняв', 5), show('Албион', 6), show('Гняв', 7)]);
    importShows($theatre, [show('Гняв', 5), show('Гняв', 7)]);

    importShows($theatre, [show('Гняв', 5), show('Албион', 6), show('Гняв', 7)]);

    expect(Performance::where('is_cancelled', true)->count())->toBe(0);
});
