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
