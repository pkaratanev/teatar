<?php

use App\Adapters\ScrapedPerformance;
use App\Adapters\TbaAdapter;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Performance;
use App\Models\Production;
use App\Models\Theatre;
use Illuminate\Support\Facades\Http;

function tbaFixture(): string
{
    return file_get_contents(base_path('tests/Fixtures/tba/programme-2026-11.html'));
}

it('parses every row, including guest performances', function () {
    $rows = collect((new TbaAdapter)->parse(tbaFixture(), 2026, 11));

    expect($rows)->toHaveCount(11)
        ->and($rows->pluck('title')->unique()->values()->all())->toBe([
            'Бариерата', 'Железният светилник', 'Гняв', 'Кафе с претенция', 'Лекция №3 за "СИЛАТА НА СЛОВОТО"',
            'Мечтата на идиотката', 'Балдахинът', 'Басейнът', 'Урок по български', 'Времето',
        ]);

    $guests = $rows->filter(fn (ScrapedPerformance $p) => $p->visitingCompany !== null);
    expect($guests->map(fn ($p) => [$p->title, $p->visitingCompany])->values()->all())->toBe([
        ['Бариерата', 'ДТ-Пловдив'],
        ['Лекция №3 за "СИЛАТА НА СЛОВОТО"', 'ДТ-Пловдив'],
    ]);
});

it('splits the visiting company out of a guest title', function (string $raw, string $title, ?string $company) {
    expect(TbaAdapter::splitGuest($raw))->toBe([$title, $company]);
})->with([
    ['Бариерата - Гостува ДТ-Пловдив', 'Бариерата', 'ДТ-Пловдив'],
    ['Ние, Духовата музика - Гостува Родопски театър-Смолян', 'Ние, Духовата музика', 'Родопски театър-Смолян'],
    ['Роднини - Гостува ДКТ Хасково', 'Роднини', 'ДКТ Хасково'],
    ['Лекция №3 за "СИЛАТА НА СЛОВОТО" гостува ДТ-Пловдив', 'Лекция №3 за "СИЛАТА НА СЛОВОТО"', 'ДТ-Пловдив'],
    ['"Гняв"', 'Гняв', null],
]);

it('reads date, local time and stage for each row', function () {
    $rows = collect((new TbaAdapter)->parse(tbaFixture(), 2026, 11));

    $first = $rows->firstWhere('title', 'Железният светилник');
    expect($first->title)->toBe('Железният светилник')
        ->and($first->startsAt->format('Y-m-d H:i'))->toBe('2026-11-03 19:00')
        ->and($first->startsAt->timezoneName)->toBe('Europe/Sofia')
        ->and($first->stage)->toBe('Голяма сцена')
        ->and($first->sourceUrl)->toBe('http://www.tba.art.bg/железният-светилник___1059');

    $chamber = $rows->firstWhere('title', 'Балдахинът');
    expect($chamber->startsAt->format('Y-m-d H:i'))->toBe('2026-11-11 19:30')
        ->and($chamber->stage)->toBe('Камерна сцена "МИРАКЪЛ"');

    expect($rows->firstWhere('title', 'Времето')->stage)->toBe('Сцена-клуб "МаксиМ"');
});

it('folds Latin look-alike letters so titles match the catalogue', function () {
    expect(TbaAdapter::cleanTitle('"Бaлдахинът" '))->toBe('Балдахинът')
        ->and(TbaAdapter::cleanTitle('"Кафе с претенция" '))->toBe('Кафе с претенция');
});

it('rolls a January row on a December page into the next year', function () {
    $html = '<div class="text-content"><div class="antetka-programa-2">Голяма сцена</div>'
        .'<div class="program-row-2"><div class="program-left"><div class="time">02 Януари (Събота)</div></div>'
        .'<div class="program-right"><span>19.00 ч.</span><a href="x___1">"Хамлет"</a></div></div></div>';

    expect((new TbaAdapter)->parse($html, 2026, 12)[0]->startsAt->format('Y-m-d'))->toBe('2027-01-02');
});

it('fails loudly when the page layout changes', function () {
    (new TbaAdapter)->parse('<html><body><p>Нова визия</p></body></html>', 2026, 11);
})->throws(RuntimeException::class, 'layout may have changed');

it('syncs the theatre end to end from the windows-1251 site', function () {
    $this->travelTo(now('Europe/Sofia')->setDate(2026, 11, 1));

    $html = mb_convert_encoding(tbaFixture(), 'Windows-1251', 'UTF-8');
    Http::fake([
        'www.tba.art.bg/programabg.php?year=2026&month=11' => Http::response($html, 200, ['Content-Type' => 'text/html; charset=windows-1251']),
        'www.tba.art.bg/*' => Http::response('<div class="text-content"><div class="antetka-programa-2">Голяма сцена</div></div>', 200),
    ]);

    $theatre = Theatre::factory()->create(['adapter' => 'tba', 'website' => 'http://www.tba.art.bg']);
    $existing = Production::factory()->for($theatre)->create(['title' => 'Гняв']);

    SyncTheatreProgramme::dispatchSync($theatre);

    $run = AdapterRun::sole();
    expect($run->error)->toBeNull()
        ->and($run->status)->toBe('succeeded')
        ->and($run->performances_found)->toBe(11)
        ->and($run->productions_created)->toBe(9)          // 10 titles, Гняв already existed
        ->and($existing->performances()->count())->toBe(2)
        ->and(Performance::count())->toBe(11)
        ->and(Production::where('title', 'Бариерата')->sole()->visiting_company)->toBe('ДТ-Пловдив')
        ->and($theatre->stages()->count())->toBe(3);
});
