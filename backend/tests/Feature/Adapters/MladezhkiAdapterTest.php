<?php

use App\Adapters\MladezhkiAdapter;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Theatre;
use Illuminate\Support\Facades\Http;

function mltFixture(): string
{
    return file_get_contents(base_path('tests/Fixtures/mlt/programa-2026-11.html'));
}

it('parses the programme table', function () {
    $rows = collect((new MladezhkiAdapter)->parse(mltFixture(), 2026, 11));

    expect($rows->pluck('title')->all())->toBe([
        'Феята Ванилия', 'Трите прасенца', 'Пипи Дългото чорапче', 'E.E.', 'Bai Ganyo', 'Хензел и Гретел',
    ]);

    $pippi = $rows->firstWhere('title', 'Пипи Дългото чорапче');
    expect($pippi->startsAt->format('Y-m-d H:i'))->toBe('2026-11-01 14:00')
        ->and($pippi->stage)->toBe('Голяма сцена')
        ->and($pippi->sourceUrl)->toBe('https://mlt.bg/пипи-дългото-чорапче___7363');
});

it('folds the Latin X in "Xензел" but leaves Latin titles alone', function () {
    $titles = collect((new MladezhkiAdapter)->parse(mltFixture(), 2026, 11))->pluck('title');

    expect($titles)->toContain('Хензел и Гретел')->toContain('Bai Ganyo');
});

it('returns nothing when the site shows another month instead', function () {
    // Asking for December but the page is November: December isn't published yet.
    expect((new MladezhkiAdapter)->parse(mltFixture(), 2026, 12))->toBe([]);
});

it('splits guest shows written into the title', function () {
    $html = '<h1 class="title-page">Програма Ноември 2026</h1><table class="programa"><tbody>'
        .'<tr data-href="x___1"><td><span>05/11</span></td><td>19:00</td><td><p>„Вишнева градина“ – Гостува ДКТ-Враца</p></td><td class="zala">Голяма сцена</td></tr>'
        .'</tbody></table>';

    $row = (new MladezhkiAdapter)->parse($html, 2026, 11)[0];

    expect($row->title)->toBe('Вишнева градина')->and($row->visitingCompany)->toBe('ДКТ-Враца');
});

it('fails loudly when the layout changes', function () {
    (new MladezhkiAdapter)->parse('<html><body><p>Нова визия</p></body></html>', 2026, 11);
})->throws(RuntimeException::class, 'layout may have changed');

it('syncs end to end from the windows-1251 site', function () {
    $this->travelTo(now('Europe/Sofia')->setDate(2026, 11, 1));
    Http::fake([
        'mlt.bg/programa.php?year=2026&month=11' => Http::response(mb_convert_encoding(mltFixture(), 'Windows-1251', 'UTF-8'), 200, ['Content-Type' => 'text/html; charset=windows-1251']),
        'mlt.bg/*' => Http::response(mb_convert_encoding(mltFixture(), 'Windows-1251', 'UTF-8'), 200, ['Content-Type' => 'text/html']),
    ]);

    $theatre = Theatre::factory()->create(['adapter' => 'mlt', 'website' => 'https://mlt.bg']);

    SyncTheatreProgramme::dispatchSync($theatre);

    // December and January fall back to the November page and are ignored.
    expect(AdapterRun::sole())->error->toBeNull()->performances_found->toBe(6)
        ->and($theatre->stages()->count())->toBe(3);
});
