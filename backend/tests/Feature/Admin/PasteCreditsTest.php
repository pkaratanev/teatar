<?php

use App\Enums\CatalogueSource;
use App\Enums\CreditRole;
use App\Filament\Resources\Productions\Pages\EditProduction;
use App\Models\Person;
use App\Models\Production;
use App\Models\User;
use App\Services\Credits;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

const TEAM = <<<'TXT'
Режисьор: Иван Петров
Сценография и костюми: Мария Иванова
Музика: Петър Димов
Превод: Елена Маринова
Актьори: Анна Колева (Офелия), Петър  Стоянов
Георги Георгиев - Хамлет
Видеоарт: Някой Друг
TXT;

it('parses labels, comma lists, following lines and characters', function () {
    $parsed = app(Credits::class)->parse(TEAM);
    $rows = collect($parsed['credits'])->map(fn ($c) => [$c['role']->value, $c['name'], $c['character']])->all();

    expect($rows)->toBe([
        ['director', 'Иван Петров', null],
        ['set_designer', 'Мария Иванова', null],
        ['costume_designer', 'Мария Иванова', null],
        ['composer', 'Петър Димов', null],
        ['translator', 'Елена Маринова', null],
        ['actor', 'Анна Колева', 'Офелия'],
        ['actor', 'Петър Стоянов', null],
        ['actor', 'Георги Георгиев', 'Хамлет'],
    ])->and($parsed['skipped'])->toBe(['Видеоарт: Някой Друг']);
});

it('adds credits once, reuses people by name, and fills the author', function () {
    $production = Production::factory()->create(['author' => null]);
    $existing = Person::factory()->create(['name' => 'Анна Колева']);
    $credits = app(Credits::class);

    $first = $credits->add($production, $credits->parse(TEAM."\nАвтор: Уилям Шекспир"), CatalogueSource::Admin);
    $again = $credits->add($production, $credits->parse(TEAM), CatalogueSource::Admin);

    expect($first)->toBe(9)
        ->and($again)->toBe(0)
        ->and(Person::where('name', 'Анна Колева')->count())->toBe(1)
        ->and($production->credits()->where('person_id', $existing->id)->value('character_name'))->toBe('Офелия')
        ->and($production->fresh()->author)->toBe('Уилям Шекспир');
});

it('fills a missing character instead of adding the actor twice', function () {
    $production = Production::factory()->create();
    $credits = app(Credits::class);

    $credits->add($production, $credits->parse('Актьори: Анна Колева'), CatalogueSource::Admin);
    $credits->add($production, $credits->parse('Актьори: Анна Колева (Офелия)'), CatalogueSource::Admin);

    expect($production->credits()->where('role_type', CreditRole::Actor)->count())->toBe(1)
        ->and($production->credits()->value('character_name'))->toBe('Офелия');
});

it('pastes the team from the production page in the admin', function () {
    $production = Production::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(EditProduction::class, ['record' => $production->getRouteKey()])
        ->callAction('pasteCredits', data: ['text' => "Режисьор: Иван Петров\nАктьори: Анна Колева, Петър Стоянов"])
        ->assertHasNoActionErrors()
        ->assertNotified('Добавени участия: 3');

    expect($production->credits()->count())->toBe(3);
});

it('tells apart two people with the same name with #id or #нов', function () {
    $director = Person::factory()->create(['name' => 'Стоян Радев']);
    $actor = Person::factory()->create(['name' => 'Стоян Радев']);
    $production = Production::factory()->create();
    $credits = app(Credits::class);

    $credits->add($production, $credits->parse("Режисьор: Стоян Радев #{$director->id}\nАктьори: Стоян Радев #{$actor->id} (Свидетелят)"), CatalogueSource::Admin);

    expect($production->credits()->where('role_type', CreditRole::Director)->value('person_id'))->toBe($director->id)
        ->and($production->credits()->where('role_type', CreditRole::Actor)->value('person_id'))->toBe($actor->id)
        ->and($production->credits()->where('role_type', CreditRole::Actor)->value('character_name'))->toBe('Свидетелят');

    $other = Production::factory()->create();
    $text = "Режисьор: Иван Петров\nАктьори: Иван Петров #нов";
    $credits->add($other, $credits->parse($text), CatalogueSource::Admin);
    $credits->add($other, $credits->parse($text), CatalogueSource::Admin); // pasting again makes no third one

    expect(Person::where('name', 'Иван Петров')->count())->toBe(2)
        ->and($other->credits()->count())->toBe(2);
});
