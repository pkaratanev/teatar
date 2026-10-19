<?php

use App\Enums\CreditRole;
use App\Filament\Resources\People\Pages\ManagePeople;
use App\Models\Follow;
use App\Models\Person;
use App\Models\Production;
use App\Models\User;
use App\Services\PersonMerge;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

it('moves credits and followers to the kept person and deletes the duplicate', function () {
    $keep = Person::factory()->create(['name' => 'Стоян Радев', 'bio' => null]);
    $dup = Person::factory()->create(['name' => 'Стоян  Радев', 'bio' => 'Актьор.']);
    [$a, $b] = Production::factory()->count(2)->create();

    $keep->credits()->create(['production_id' => $a->id, 'role_type' => CreditRole::Actor]);
    $dup->credits()->create(['production_id' => $a->id, 'role_type' => CreditRole::Actor]); // same credit twice
    $dup->credits()->create(['production_id' => $b->id, 'role_type' => CreditRole::Director]);

    [$u1, $u2] = User::factory()->count(2)->create();
    Follow::create(['user_id' => $u1->id, 'followable_type' => $keep->getMorphClass(), 'followable_id' => $keep->id]);
    Follow::create(['user_id' => $u1->id, 'followable_type' => $dup->getMorphClass(), 'followable_id' => $dup->id]);
    Follow::create(['user_id' => $u2->id, 'followable_type' => $dup->getMorphClass(), 'followable_id' => $dup->id]);

    $moved = app(PersonMerge::class)->merge($keep, $dup);

    expect($moved)->toBe(['credits' => 1, 'followers' => 1])
        ->and(Person::find($dup->id))->toBeNull()
        ->and($keep->credits()->count())->toBe(2)
        ->and($keep->followers()->count())->toBe(2)
        ->and($keep->fresh()->bio)->toBe('Актьор.');
});

it('refuses to merge a person into themselves', function () {
    $person = Person::factory()->create();

    app(PersonMerge::class)->merge($person, $person);
})->throws(InvalidArgumentException::class);

it('merges from the people list in the admin', function () {
    $admin = User::factory()->admin()->create();
    $keep = Person::factory()->create(['name' => 'Теодор Елмазов']);
    $dup = Person::factory()->create(['name' => 'Теодор Елмазов']);
    $production = Production::factory()->create();
    $dup->credits()->create(['production_id' => $production->id, 'role_type' => CreditRole::Actor]);

    $this->actingAs($admin);
    Livewire::test(ManagePeople::class)
        ->callTableAction('merge', $dup, data: ['keep_id' => $keep->id])
        ->assertHasNoTableActionErrors()
        ->assertNotified();

    expect(Person::find($dup->id))->toBeNull()
        ->and($keep->credits()->count())->toBe(1);
});

it('shows each person\'s productions in the list, so namesakes can be told apart', function () {
    $director = Person::factory()->create(['name' => 'Стоян Радев']);
    $production = Production::factory()->create(['title' => 'Сън в зимна нощ']);
    $director->credits()->create(['production_id' => $production->id, 'role_type' => CreditRole::Director]);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManagePeople::class)
        ->assertSee('Сън в зимна нощ (режисьор');
});

it('groups probable duplicates: middle names, short first names, nicknames, dash styles, initials', function () {
    $names = ['Христо Станчев', 'Христо Ботев Станчев', 'Любо Нейков', 'Любомир Нейков', 'Александър Карасански-Джош',
        'Александър Карасански', 'Димитър Коцев – Шошо', 'Димитър Коцев-Шошо', 'Стоян Радев', 'Стоян Радев Ге. К.', 'Иван Петров'];
    foreach ($names as $name) {
        Person::factory()->create(['name' => $name]);
    }

    $groups = app(App\Services\PeopleDuplicates::class)->groups()
        ->map(fn ($people) => $people->pluck('name')->sort()->values()->all())->values()->all();

    expect($groups)->toHaveCount(5)
        ->and($groups)->toContain(['Христо Ботев Станчев', 'Христо Станчев'])
        ->and($groups)->toContain(['Любо Нейков', 'Любомир Нейков'])
        ->and($groups)->toContain(['Стоян Радев', 'Стоян Радев Ге. К.'])
        ->and(collect($groups)->flatten())->not->toContain('Иван Петров');
});

it('shows only probable duplicates with the filter, and merges several at once', function () {
    $keep = Person::factory()->create(['name' => 'Стоян Радев']);
    $dupA = Person::factory()->create(['name' => 'Стоян Радев Ге. К.']);
    $dupB = Person::factory()->create(['name' => 'стоян  радев']);
    $alone = Person::factory()->create(['name' => 'Иван Петров']);
    [$p1, $p2] = Production::factory()->count(2)->create();
    $dupA->credits()->create(['production_id' => $p1->id, 'role_type' => CreditRole::Actor]);
    $dupB->credits()->create(['production_id' => $p2->id, 'role_type' => CreditRole::Director]);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManagePeople::class)
        ->filterTable('duplicates')
        ->assertCanSeeTableRecords([$keep, $dupA, $dupB])
        ->assertCanNotSeeTableRecords([$alone])
        ->callTableBulkAction('mergeSelected', [$keep, $dupA, $dupB], data: ['keep_id' => $keep->id])
        ->assertHasNoTableBulkActionErrors()
        ->assertNotified();

    expect(Person::whereIn('id', [$dupA->id, $dupB->id])->count())->toBe(0)
        ->and($keep->credits()->count())->toBe(2);
});
