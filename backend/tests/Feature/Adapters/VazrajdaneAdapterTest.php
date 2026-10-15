<?php

use App\Adapters\VazrajdaneAdapter;
use App\Enums\CreditRole;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Credit;
use App\Models\Person;
use App\Models\Production;
use App\Models\Theatre;
use Illuminate\Support\Facades\Http;

function vazrajdaneFixture(): string
{
    return file_get_contents(base_path('tests/Fixtures/vazrajdane/programa-2026-11.html'));
}

it('parses every event card', function () {
    $rows = collect((new VazrajdaneAdapter)->parse(vazrajdaneFixture(), 2026, 11));

    expect($rows->pluck('title')->all())->toBe([
        'Прелестите на изневярата', 'Те бяха 10', 'Снежанка и седемте джуджета', 'Иванов', 'Квартет за двама',
    ]);

    $kids = $rows->firstWhere('title', 'Снежанка и седемте джуджета');
    expect($kids->startsAt->format('Y-m-d H:i'))->toBe('2026-11-07 11:00')
        ->and($kids->stage)->toBeNull()
        ->and($kids->author)->toBe('Братя Грим')
        ->and($kids->director)->toBe('Христо Ботев Станчев')
        ->and($kids->ticketUrl)->toBe('https://www.entase.com/book?eid=6a9a95cb42edcfd21009a684');
});

it('reads both "Автор - X" and "Автор: X" credit lines', function () {
    $rows = collect((new VazrajdaneAdapter)->parse(vazrajdaneFixture(), 2026, 11));

    expect($rows->firstWhere('title', 'Иванов'))
        ->author->toBe('А.П.Чехов')
        ->director->toBe('Стилиян Петров');
});

it('marks guest shows by their category', function () {
    $guests = collect((new VazrajdaneAdapter)->parse(vazrajdaneFixture(), 2026, 11))->filter->guest();

    expect($guests->pluck('title')->all())->toBe(['Прелестите на изневярата']);
});

it('creates one performance per listed time', function () {
    $html = '<div class="event_items"><div class="event_item category-za-deca"><div>'
        .'<div class="event_entase_title">Пипи</div>'
        .'<div class="event_entase_field event_entase_dateonly"><p>14 Ное</p><p>събота</p></div>'
        .'<div class="event_entase_field event_entase_timeonly">11:00 ч. и 12:30 ч.</div>'
        .'</div></div></div>';

    $times = collect((new VazrajdaneAdapter)->parse($html, 2026, 11))->map(fn ($p) => $p->startsAt->format('H:i'))->all();

    expect($times)->toBe(['11:00', '12:30']);
});

it('fails loudly when the layout changes', function () {
    (new VazrajdaneAdapter)->parse('<html><body><p>Нова визия</p></body></html>', 2026, 11);
})->throws(RuntimeException::class, 'layout may have changed');

it('syncs end to end and adds director credits once', function () {
    $this->travelTo(now('Europe/Sofia')->setDate(2026, 11, 1));
    Http::fake([
        'theatrevazrajdane.bg/programa/?date=20261101-20261130' => Http::response(vazrajdaneFixture()),
        'theatrevazrajdane.bg/*' => Http::response('<div class="event_items"></div>'),
    ]);

    $theatre = Theatre::factory()->create(['adapter' => 'vazrajdane', 'website' => 'https://theatrevazrajdane.bg']);

    SyncTheatreProgramme::dispatchSync($theatre);
    SyncTheatreProgramme::dispatchSync($theatre); // second run must not duplicate credits

    expect(AdapterRun::where('status', 'succeeded')->count())->toBe(2)
        ->and(Production::count())->toBe(5)
        ->and(Credit::where('role_type', CreditRole::Director)->count())->toBe(5)
        ->and(Person::where('name', 'Стилиян Петров')->count())->toBe(1)
        ->and(Production::where('title', 'Прелестите на изневярата')->sole()->is_guest)->toBeTrue();
});
