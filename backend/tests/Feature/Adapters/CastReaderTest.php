<?php

use App\Adapters\Cast\DramaVarnaCastReader;
use App\Adapters\Cast\NationalCastReader;
use App\Adapters\Cast\SalzaISmyahCastReader;
use App\Adapters\Cast\SatirataCastReader;
use App\Adapters\Cast\SofiaTheatreCastReader;
use App\Adapters\Cast\TbaCastReader;
use App\Adapters\Cast\Theatre199CastReader;
use App\Adapters\Cast\TheatroCastReader;
use App\Adapters\Cast\VazrajdaneCastReader;
use App\Adapters\Cast\ZadKanalaCastReader;
use App\Enums\CreditRole;
use App\Models\Person;
use App\Models\Production;
use App\Models\Theatre;
use App\Services\CastSync;
use App\Services\Credits;
use App\Support\Encoding;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

/** @return array{credits: array<string, list<string>>, author: ?string} role => names, as read from a saved page */
function castFrom(string $readerClass, string $fixture): array
{
    $html = Encoding::toUtf8(file_get_contents(base_path("tests/Fixtures/{$fixture}")));
    $parsed = app(Credits::class)->fromPairs(app($readerClass)->read($html));

    $byRole = [];
    foreach ($parsed['credits'] as $c) {
        $byRole[$c['role']->value][] = $c['name'];
    }

    return ['credits' => $byRole, 'author' => $parsed['author']];
}

it('reads Възраждане: details box, double surnames, titles dropped', function () {
    $cast = castFrom(VazrajdaneCastReader::class, 'vazrajdane/production-ivanov.html');

    expect($cast['author'])->toBe('А.П.Чехов')
        ->and($cast['credits']['translator'])->toBe(['Людмил Димитров'])
        ->and($cast['credits']['director'])->toBe(['Стилиян Петров'])
        ->and($cast['credits']['set_designer'])->toBe(['Никола Тороманов'])
        ->and($cast['credits']['costume_designer'])->toBe(['Ясмин Манделли'])
        ->and($cast['credits']['composer'])->toBe(['Милен Кукошаров'])
        ->and($cast['credits']['actor'])->toHaveCount(11)
        ->and($cast['credits']['actor'])->toContain('Мариана Жикич', 'Донка Аврамова-Бочева', 'Калоян Карагинев');

    $other = castFrom(VazrajdaneCastReader::class, 'vazrajdane/production-te-byaha-deset.html');
    expect($other['credits']['playwright'])->toBe(['Агата Кристи'])
        ->and($other['credits']['director'])->toBe(['Йосиф Сърчаджиев'])
        ->and($other['credits']['actor'])->toHaveCount(10)
        ->and($other['credits']['actor'])->toContain('Христо Ботев Станчев');
});

it('reads Българска армия: the credits block only, not the cast table under it', function () {
    $cast = castFrom(TbaCastReader::class, 'tba/production-gnyav.html');

    expect($cast['author'])->toBe('Елизабет Динкова по романа на Стивън Кинг')
        ->and($cast['credits']['playwright'])->toBe(['Елизабет Динкова'])
        ->and($cast['credits']['director'])->toBe(['Стефан Спасов'])
        ->and($cast['credits']['set_designer'])->toBe(['Нина Пашова'])
        ->and($cast['credits']['costume_designer'])->toBe(['Нина Пашова'])
        ->and($cast['credits']['composer'])->toBe(['Явор Карагитлиев'])
        ->and($cast['credits']['actor'])->toHaveCount(12)
        ->and($cast['credits']['actor'])->toContain('Симеон Дамянов', 'Радосвета Василева');

    $other = castFrom(TbaCastReader::class, 'tba/production-mechtata.html');
    expect($other['credits']['director'])->toBe(['Валентин Танев']) // "Постановка"
        ->and($other['credits']['composer'])->toBe(['Михаил Шишков']) // "Музикална среда"
        ->and($other['credits']['actor'])->toBe(['Стефка Янорова', 'Василена Винченцо', 'Явор Намлиев']);
});

it('reads Зад канала: labelled fields and the "от …" author', function () {
    $cast = castFrom(ZadKanalaCastReader::class, 'zadkanala/production-velika.html');

    expect($cast['author'])->toBe('Тони Макнамара')
        ->and($cast['credits']['director'])->toBe(['Стайко Мурджев'])
        ->and($cast['credits']['translator'])->toBe(['Любов Костова'])
        ->and($cast['credits']['choreographer'])->toBe(['Ангелина Гаврилова-Тодорова'])
        ->and($cast['credits']['actor'])->toHaveCount(10)
        ->and($cast['credits']['actor'])->toContain('Весела Бабинова', 'Александър Кендеров');

    $other = castFrom(ZadKanalaCastReader::class, 'zadkanala/production-syn.html');
    expect($other['credits']['director'])->toBe(['Стоян Радев'])
        ->and($other['credits']['actor'])->toContain('Ирини Жамбонас', 'Луиза Григорова-Макариев');
});

it('reads Théatro in all three layouts', function () {
    $lines = castFrom(TheatroCastReader::class, 'theatro/production-iluzii.html');
    expect($lines['author'])->toBe('Иван Вирипаев')
        ->and($lines['credits']['director'])->toBe(['Нено Койнарски'])
        ->and($lines['credits']['set_designer'])->toBe(['Ясмин Манделли'])
        ->and($lines['credits']['actor'])->toBe(['Антъни Пенев', 'Боян Петров', 'Ванеса Пеянкова', 'Жаклин Даскалова']);

    $sidebar = castFrom(TheatroCastReader::class, 'theatro/production-svatba.html');
    expect($sidebar['credits']['director'])->toBe(['Николай Гундеров'])
        ->and($sidebar['credits']['translator'])->toBe(['Тони Минасян'])
        ->and($sidebar['credits']['actor'])->toContain('Филип Аврамов', 'Христина Джурова');

    $dashes = castFrom(TheatroCastReader::class, 'theatro/production-literatura.html');
    expect($dashes['author'])->toBe('Иво Сиромахов')
        ->and($dashes['credits']['director'])->toBe(['Златомир Молдовански'])
        ->and($dashes['credits']['set_designer'])->toBe(['Ивелина Минева'])
        ->and($dashes['credits']['actor'])->toContain('Стефани Ивайло', 'Ненчо Илчев', 'Мария Игнатова', 'Антоан Петров')
        ->and($dashes['credits'])->not->toHaveKey('composer'); // "D-Trax" is not a person's name
});

it('reads Народен театър: team section and actors carousel', function () {
    $cast = castFrom(NationalCastReader::class, 'national/production-az-plasham.html');

    expect($cast['credits']['director'])->toBe(['Владлен Александров'])
        ->and($cast['credits']['set_designer'])->toBe(['Елена Иванова'])
        ->and($cast['credits']['costume_designer'])->toBe(['Елена Иванова'])
        ->and($cast['credits']['choreographer'])->toBe(['Ивайло Александров'])
        ->and($cast['credits']['actor'])->toHaveCount(7)
        ->and($cast['credits']['actor'])->toContain('Аня Пенчева', 'Христо Терзиев');

    expect(castFrom(NationalCastReader::class, 'national/production-ariya.html')['credits']['actor'])->toBe(['Биляна Петринска']);
});

it('reads Театър 199: labelled list, "участва" in the singular too', function () {
    $cast = castFrom(Theatre199CastReader::class, 'theatre199/production-vecheren-akt.html');
    expect($cast['credits']['director'])->toBe(['Съни Сънински']) // "постановка"
        ->and($cast['credits']['actor'])->toBe(['Мария Сапунджиева', 'Съни Сънински', 'Георги Спасов']);

    expect(castFrom(Theatre199CastReader::class, 'theatre199/production-godinata.html')['credits']['actor'])->toBe(['Анастасия Лютова']);
});

it('reads Сатирата: characters before the actor, names in capitals, alternates, no assistants', function () {
    $cast = castFrom(SatirataCastReader::class, 'satirata/production-tom-i-huk.html');
    $actors = collect(app(Credits::class)->fromPairs(app(SatirataCastReader::class)->read(
        file_get_contents(base_path('tests/Fixtures/satirata/production-tom-i-huk.html'))
    ))['credits'])->where('role', CreditRole::Actor)->mapWithKeys(fn ($c) => [$c['name'] => $c['character']]);

    expect($cast['author'])->toBe('Марк Твен')
        ->and($cast['credits']['director'])->toBe(['Анастасия Събева']) // not the "помощник-режисьор"
        ->and($cast['credits']['actor'])->toHaveCount(10)
        ->and($actors['Тодор Лазаров'])->toBe('Том Сойер')
        ->and($actors['Теодор Елмазов'])->toBe('Учител, Адвокат');

    $other = castFrom(SatirataCastReader::class, 'satirata/production-primadoni.html');
    expect($other['author'])->toBe('Кен Лудвиг')
        ->and($other['credits']['director'])->toBe(['Калин Сърменов'])
        ->and($other['credits']['composer'])->toBe(['Асен Аврамов']) // the "Музиканти" are not composers
        ->and($other['credits']['actor'])->toContain('Анита Ангелова', 'Петър Калчев', 'Николай Върбанов');
});

it('reads Театър „София“: team boxes and the cast in capitals, once', function () {
    $cast = castFrom(SofiaTheatreCastReader::class, 'sofia/production-lyubov.html');

    expect($cast['credits']['director'])->toBe(['Ованес Торосян'])
        ->and($cast['credits']['set_designer'])->toBe(['Мера Денева'])
        ->and($cast['credits']['composer'])->toBe(['Елена Дечева'])
        ->and($cast['credits']['actor'])->toHaveCount(12)
        ->and($cast['credits']['actor'])->toContain('Ангела Канева', 'Гринго-Богдан Григоров');
});

it('reads Сълза и смях: "Label <strong>Names</strong>" lines and the "от …" author', function () {
    $cast = castFrom(SalzaISmyahCastReader::class, 'salzaismyah/production-az-dosadnikat.html');

    expect($cast['author'])->toBe('Франсис Вебер')
        ->and($cast['credits']['translator'])->toBe(['Снежина Русинова-Здравкова'])
        ->and($cast['credits']['director'])->toBe(['Богдан Петканин'])
        ->and($cast['credits']['actor'])->toHaveCount(7);

    expect(castFrom(SalzaISmyahCastReader::class, 'salzaismyah/production-feyata.html')['credits']['composer'])->toBe(['Мира Искърова']);
});

it('reads Драматичен театър Варна: the one-line team and the cast list', function () {
    $cast = castFrom(DramaVarnaCastReader::class, 'dramavarna/production-pod-igoto.html');

    expect($cast['credits']['director'])->toBe(['Бина Харалампиева'])
        ->and($cast['credits']['set_designer'])->toBe(['Свила Величкова'])
        ->and($cast['credits']['composer'])->toBe(['Асен Аврамов'])
        ->and($cast['credits']['actor'])->toHaveCount(16);

    $other = castFrom(DramaVarnaCastReader::class, 'dramavarna/production-zashtoto.html');
    expect($other['author'])->toBeNull() // "Текст на песните" is not the play's author
        ->and($other['credits'])->not->toHaveKey('playwright')
        ->and($other['credits']['actor'])->toContain('Недялко Стефанов', 'Даниела Викторова');
});

it('only reads production pages, never a programme page', function () {
    expect(app(TheatroCastReader::class)->accepts('https://www.theatro.bg/program/'))->toBeFalse()
        ->and(app(TheatroCastReader::class)->accepts('https://www.theatro.bg/portfolio_page/илюзии/'))->toBeTrue()
        ->and(app(ZadKanalaCastReader::class)->accepts('https://zadkanala.bg/programa'))->toBeFalse()
        ->and(app(TbaCastReader::class)->accepts('http://www.tba.art.bg/programabg.php?year=2026&month=11'))->toBeFalse();
});

it('fills productions without actors after a sync, once, and puts them on the review list', function () {
    config(['teatar.cast_reader.per_run' => 25]);
    Sleep::fake();
    Http::fake([
        'zadkanala.bg/spektakli/velika' => Http::response(file_get_contents(base_path('tests/Fixtures/zadkanala/production-velika.html'))),
        '*' => Http::response('', 404),
    ]);

    $theatre = Theatre::factory()->create(['adapter' => 'zadkanala']);
    $velika = Production::factory()->for($theatre)->create(['title' => 'Велика', 'author' => null, 'source_url' => 'https://zadkanala.bg/spektakli/velika']);
    $missing = Production::factory()->for($theatre)->create(['source_url' => 'https://zadkanala.bg/spektakli/gone']);
    $programme = Production::factory()->for($theatre)->create(['source_url' => 'https://zadkanala.bg/programa']);
    Person::factory()->create(['name' => 'Стайко Мурджев']); // already known: reused

    $added = app(CastSync::class)->fill($theatre);

    expect($added)->toBe(17) // 10 actors + director, set, music, costumes, translator, choreographer + playwright
        ->and($velika->fresh()->author)->toBe('Тони Макнамара')
        ->and($velika->fresh()->needs_review)->toBeTrue()
        ->and(Person::where('name', 'Стайко Мурджев')->count())->toBe(1)
        ->and($velika->credits()->where('role_type', CreditRole::Actor)->count())->toBe(10)
        ->and($missing->fresh()->credits_checked_at)->not->toBeNull()
        ->and($programme->fresh()->credits_checked_at)->toBeNull();

    // 10 s between the two Зад канала pages (robots.txt Crawl-delay); the HTTP client's own retry pauses are shorter.
    Sleep::assertSlept(fn (Carbon\CarbonInterval $d) => (int) $d->totalSeconds === 10, 1);
    expect(app(CastSync::class)->fill($theatre))->toBe(0); // read once; not again for 30 days
});

it('is off in tests by default, so syncs never reach theatre sites', function () {
    $theatre = Theatre::factory()->create(['adapter' => 'zadkanala']);
    Production::factory()->for($theatre)->create(['source_url' => 'https://zadkanala.bg/spektakli/velika']);
    Http::fake();

    expect(app(CastSync::class)->fill($theatre))->toBe(0);
    Http::assertNothingSent();
});
