<?php

use App\Adapters\ScrapedPerformance;
use App\Enums\CreditRole;
use App\Filament\Resources\ProductionReviews\Pages\ListProductionReviews;
use App\Filament\Resources\ProductionReviews\ProductionReviewResource;
use App\Filament\Resources\Productions\Pages\EditProduction;
use App\Models\Credit;
use App\Models\Performance;
use App\Models\Person;
use App\Models\Production;
use App\Models\Theatre;
use App\Models\User;
use App\Services\ProgrammeImporter;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

function soon(string $title, ?string $author = null, ?string $director = null): ScrapedPerformance
{
    return new ScrapedPerformance($title, CarbonImmutable::now('Europe/Sofia')->addDays(5)->setTime(19, 0), author: $author, director: $director);
}

it('flags a production the importer creates', function () {
    $theatre = Theatre::factory()->create();

    app(ProgrammeImporter::class)->import($theatre, [soon('Гняв')]);

    expect(Production::sole())->needs_review->toBeTrue()->review_reason->toBe('new');
});

it('does not flag productions an admin creates', function () {
    expect(Production::factory()->create()->fresh()->needs_review)->toBeFalse();
});

it('flags a reviewed production again when an import adds data, and not when nothing changes', function () {
    $theatre = Theatre::factory()->create();
    app(ProgrammeImporter::class)->import($theatre, [soon('Гняв')]);
    $production = Production::sole();
    $production->markReviewed(null);

    app(ProgrammeImporter::class)->import($theatre, [soon('Гняв')]);
    expect($production->fresh()->needs_review)->toBeFalse();

    app(ProgrammeImporter::class)->import($theatre, [soon('Гняв', author: 'Нийл Лабют', director: 'Иван Иванов')]);
    expect($production->fresh())->needs_review->toBeTrue()->review_reason->toBe('changed');
});

it('keeps a new production marked new when the same run adds a director', function () {
    $theatre = Theatre::factory()->create();

    app(ProgrammeImporter::class)->import($theatre, [soon('Гняв', director: 'Иван Иванов')]);

    expect(Production::sole()->review_reason)->toBe('new');
});

it('lists what is missing', function () {
    $production = Production::factory()->create(['author' => 'Чехов', 'synopsis' => null]);
    Credit::create(['production_id' => $production->id, 'person_id' => Person::factory()->create()->id, 'role_type' => CreditRole::Director]);

    expect($production->missingData())->toBe(['Актьори', 'Плакат', 'Описание'])
        ->and(Production::withDataChecks()->find($production->id)->missingData())->toBe(['Актьори', 'Плакат', 'Описание']);
});

it('shows flagged productions playing soon, with a badge', function () {
    $soon = Production::factory()->create(['title' => 'Скоро', 'needs_review' => true, 'review_reason' => 'new']);
    Performance::factory()->for($soon)->create(['starts_at' => now()->addDays(3)]);
    $later = Production::factory()->create(['title' => 'Късно', 'needs_review' => true, 'review_reason' => 'new']);
    Performance::factory()->for($later)->create(['starts_at' => now()->addDays(60)]);
    $done = Production::factory()->create(['title' => 'Готово', 'needs_review' => false]);
    Performance::factory()->for($done)->create(['starts_at' => now()->addDays(3)]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/production-reviews')
        ->assertOk()
        ->assertSee('Скоро')
        ->assertDontSee('Късно')
        ->assertDontSee('Готово');

    expect(ProductionReviewResource::getNavigationBadge())->toBe('1');
});

it('marks a production reviewed from the list, recording who and when', function () {
    $admin = User::factory()->admin()->create();
    $production = Production::factory()->create(['needs_review' => true, 'review_reason' => 'new']);
    Performance::factory()->for($production)->create(['starts_at' => now()->addDays(3)]);

    Livewire::actingAs($admin)
        ->test(ListProductionReviews::class)
        ->callTableAction('reviewed', $production);

    expect($production->fresh())
        ->needs_review->toBeFalse()
        ->reviewed_by->toBe($admin->id)
        ->reviewed_at->not->toBeNull();
});

it('marks several reviewed at once', function () {
    $productions = Production::factory()->count(2)->create(['needs_review' => true, 'review_reason' => 'new']);
    $productions->each(fn ($p) => Performance::factory()->for($p)->create(['starts_at' => now()->addDays(3)]));

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListProductionReviews::class)
        ->callTableBulkAction('reviewed', $productions);

    expect(Production::where('needs_review', true)->count())->toBe(0);
});

it('has "Прегледано" on the edit page and shows who reviewed', function () {
    $admin = User::factory()->admin()->create(['name' => 'Админ Админов']);
    $production = Production::factory()->create(['needs_review' => true, 'review_reason' => 'changed']);

    $this->actingAs($admin)->get("/admin/productions/{$production->id}/edit")->assertOk()->assertSee('За преглед (променена от импорта)');

    Livewire::actingAs($admin)
        ->test(EditProduction::class, ['record' => $production->getRouteKey()])
        ->callAction('reviewed');

    $this->actingAs($admin)->get("/admin/productions/{$production->id}/edit")->assertSee('от Админ Админов');
});
