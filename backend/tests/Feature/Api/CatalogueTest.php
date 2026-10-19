<?php

use App\Enums\CreditRole;
use App\Models\Credit;
use App\Models\Performance;
use App\Models\Person;
use App\Models\Production;
use App\Models\Theatre;

it('lists this week\'s Sofia programme in date order', function () {
    $production = Production::factory()->create();
    Performance::factory()->for($production)->create(['starts_at' => now()->addDays(3)]);
    Performance::factory()->for($production)->create(['starts_at' => now()->addDay()]);
    Performance::factory()->for($production)->create(['starts_at' => now()->addDays(20)]); // outside the week
    Performance::factory()->for($production)->create(['starts_at' => now()->addDays(2), 'is_cancelled' => true]);

    $response = $this->getJson('/api/v1/performances?range=week')->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('data.0.starts_at') < $response->json('data.1.starts_at'))->toBeTrue();
    expect($response->json('data.0.production.title'))->toBe($production->title);
});

it('excludes theatres in other cities and inactive theatres', function () {
    $varna = Production::factory()->for(Theatre::factory()->create(['city' => 'Варна']))->create();
    $inactive = Production::factory()->for(Theatre::factory()->create(['is_active' => false]))->create();
    Performance::factory()->for($varna)->create(['starts_at' => now()->addDay()]);
    Performance::factory()->for($inactive)->create(['starts_at' => now()->addDay()]);

    $this->getJson('/api/v1/performances')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/performances?city=Варна')->assertOk()->assertJsonCount(1, 'data');
});

it('shows a production with its credits and upcoming dates', function () {
    $production = Production::factory()->create();
    $person = Person::factory()->create();
    Credit::create(['production_id' => $production->id, 'person_id' => $person->id, 'role_type' => CreditRole::Director]);
    Performance::factory()->for($production)->create(['starts_at' => now()->addDay()]);
    Performance::factory()->for($production)->create(['starts_at' => now()->subDay()]);

    $this->getJson("/api/v1/productions/{$production->id}")
        ->assertOk()
        ->assertJsonPath('data.title', $production->title)
        ->assertJsonPath('data.credits.0.person.name', $person->name)
        ->assertJsonPath('data.credits.0.role_label', 'Режисьор')
        ->assertJsonCount(1, 'data.upcoming_performances');
});

it('searches productions, people and theatres', function () {
    Production::factory()->create(['title' => 'Майстора и Маргарита']);
    Person::factory()->create(['name' => 'Маргарита Иванова']);

    $this->getJson('/api/v1/search?q='.urlencode('Маргарита'))
        ->assertOk()
        ->assertJsonCount(1, 'productions')
        ->assertJsonCount(1, 'people');
});

it('lists theatres with how many performances are coming up', function () {
    $busy = Theatre::factory()->create(['name' => 'А театър']);
    Theatre::factory()->create(['name' => 'Б театър']);
    Performance::factory()->for(Production::factory()->for($busy))->create(['starts_at' => now()->addDay()]);
    Performance::factory()->for(Production::factory()->for($busy))->create(['starts_at' => now()->subDay()]);

    $this->getJson('/api/v1/theatres')
        ->assertOk()
        ->assertJsonPath('data.0.upcoming_count', 1)
        ->assertJsonPath('data.1.upcoming_count', 0);
});

it('pages through a long programme', function () {
    $production = Production::factory()->create();
    foreach (range(1, 60) as $i) {
        Performance::factory()->for($production)->create(['starts_at' => now()->addHours($i)]);
    }

    $first = $this->getJson('/api/v1/performances?range=month')->assertOk();
    expect($first->json('meta.last_page'))->toBe(2)->and($first->json('data'))->toHaveCount(50);

    $this->getJson('/api/v1/performances?range=month&page=2')->assertOk()->assertJsonCount(10, 'data');
});

it('lists cities with active theatres, biggest programme first', function () {
    $sofia = Production::factory()->for(Theatre::factory()->create(['city' => 'София']))->create();
    foreach ([1, 2, 3] as $day) {
        Performance::factory()->for($sofia)->create(['starts_at' => now()->addDays($day)]);
    }
    $varna = Production::factory()->for(Theatre::factory()->create(['city' => 'Варна']))->create();
    Performance::factory()->for($varna)->create(['starts_at' => now()->addDays(2)]);
    Performance::factory()->for($varna)->create(['starts_at' => now()->subDays(2)]); // past, not counted
    Theatre::factory()->create(['city' => 'Варна']);
    Theatre::factory()->create(['city' => 'Пловдив', 'is_active' => false]);

    $this->getJson('/api/v1/cities')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'София')
        ->assertJsonPath('data.0.upcoming_count', 3)
        ->assertJsonPath('data.1.name', 'Варна')
        ->assertJsonPath('data.1.theatres_count', 2)
        ->assertJsonPath('data.1.upcoming_count', 1);
});

it('gives each theatre a short name for chips, falling back to the full name', function () {
    \App\Models\Theatre::factory()->create(['name' => 'Народен театър „Иван Вазов“', 'short_name' => 'Народен театър']);
    \App\Models\Theatre::factory()->create(['name' => 'Théatro', 'short_name' => null]);

    $names = collect($this->getJson('/api/v1/theatres?city=София')->assertOk()->json('data'))->pluck('short_name', 'name');

    expect($names['Народен театър „Иван Вазов“'])->toBe('Народен театър')
        ->and($names['Théatro'])->toBe('Théatro');
});

it('seeds short names for the launch theatres', function () {
    $this->seed(\Database\Seeders\LaunchTheatresSeeder::class);

    expect(\App\Models\Theatre::whereNull('short_name')->count())->toBe(0)
        ->and(\App\Models\Theatre::where('slug', \Illuminate\Support\Str::slug('Народен театър „Иван Вазов“'))->value('short_name'))->toBe('Народен театър');
});
