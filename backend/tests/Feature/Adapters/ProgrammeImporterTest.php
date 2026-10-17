<?php

use App\Adapters\ScrapedPerformance;
use App\Models\Performance;
use App\Models\Production;
use App\Models\Theatre;
use App\Services\ProgrammeImporter;
use Carbon\CarbonImmutable;

function scraped(string $title, string $when, ?string $stage = 'Голяма сцена'): ScrapedPerformance
{
    return new ScrapedPerformance($title, CarbonImmutable::parse($when, 'Europe/Sofia'), $stage);
}

it('creates productions, stages and performances from scraped rows', function () {
    $theatre = Theatre::factory()->create();

    $stats = app(ProgrammeImporter::class)->import($theatre, [
        scraped('Гняв', '2026-11-03 19:00'),
        scraped('Гняв', '2026-11-10 19:00'),
        scraped('Албион', '2026-11-04 19:30', 'Камерна сцена'),
    ]);

    expect($stats)->toBe(['found' => 3, 'upserted' => 3, 'productions_created' => 2, 'posters' => 0, 'moved' => 0, 'cancelled' => 0, 'held_back' => 0])
        ->and($theatre->productions()->count())->toBe(2)
        ->and($theatre->stages()->pluck('name')->sort()->values()->all())->toBe(['Голяма сцена', 'Камерна сцена'])
        ->and(Performance::count())->toBe(3);
});

it('is idempotent when the same programme is imported twice', function () {
    $theatre = Theatre::factory()->create();
    $rows = [scraped('Гняв', '2026-11-03 19:00'), scraped('Албион', '2026-11-04 19:30')];

    app(ProgrammeImporter::class)->import($theatre, $rows);
    $second = app(ProgrammeImporter::class)->import($theatre, $rows);

    expect($second)->toBe(['found' => 2, 'upserted' => 0, 'productions_created' => 0, 'posters' => 0, 'moved' => 0, 'cancelled' => 0, 'held_back' => 0])
        ->and(Performance::count())->toBe(2);
});

it('matches existing productions regardless of case and quote style', function () {
    $theatre = Theatre::factory()->create();
    $existing = Production::factory()->for($theatre)->create(['title' => '„Мечтата на идиотката“']);

    $stats = app(ProgrammeImporter::class)->import($theatre, [scraped('"МЕЧТАТА НА ИДИОТКАТА"', '2026-11-05 19:00')]);

    expect($stats['productions_created'])->toBe(0)
        ->and($existing->performances()->count())->toBe(1);
});

it('stores local programme times in UTC', function () {
    $theatre = Theatre::factory()->create();

    app(ProgrammeImporter::class)->import($theatre, [scraped('Гняв', '2026-11-03 19:00')]);

    // Sofia is UTC+2 in November.
    expect(Performance::first()->starts_at->utc()->format('Y-m-d H:i'))->toBe('2026-11-03 17:00');
});

it('keeps a guest production apart from a home production with the same title', function () {
    $theatre = Theatre::factory()->create();
    $home = Production::factory()->for($theatre)->create(['title' => 'Хамлет']);

    $guest = new ScrapedPerformance('Хамлет', CarbonImmutable::parse('2026-11-20 19:00', 'Europe/Sofia'), visitingCompany: 'ДТ-Варна');
    $stats = app(ProgrammeImporter::class)->import($theatre, [$guest, scraped('Хамлет', '2026-11-21 19:00')]);

    expect($stats['productions_created'])->toBe(1)
        ->and($home->performances()->count())->toBe(1)
        ->and(Production::where('visiting_company', 'ДТ-Варна')->sole()->performances()->count())->toBe(1);
});

it('stores long titles, credits and links from theatre sites (Postgres rejects text over a column length)', function () {
    $theatre = Theatre::factory()->create();
    $title = str_repeat('Дълго заглавие на постановка ', 12); // ~350 characters, ~500 once transliterated
    $url = 'https://tickets.example.test/event?'.str_repeat('utm_source=teatar&', 30);

    app(ProgrammeImporter::class)->import($theatre, [new ScrapedPerformance(
        title: $title,
        startsAt: CarbonImmutable::parse('2026-11-03 19:00', 'Europe/Sofia'),
        stage: 'Голяма сцена',
        ticketUrl: $url,
        sourceUrl: $url,
        author: str_repeat('Автор ', 60),
        ageAdvisory: 'Подходящо за деца над 12 години',
    )]);

    $production = Production::sole();
    expect(mb_strlen($production->title))->toBeGreaterThan(255)
        ->and(strlen($production->slug))->toBeLessThanOrEqual(86)
        ->and($production->age_advisory)->toBeNull()
        ->and(Performance::sole()->ticket_url)->toBe($url);
});
