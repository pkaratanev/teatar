<?php

use App\Enums\CreditRole;
use App\Models\AdapterRun;
use App\Models\Credit;
use App\Models\Performance;
use App\Models\Person;
use App\Models\Production;
use App\Models\Review;
use App\Models\Theatre;
use App\Models\User;

beforeEach(function () {
    $theatre = Theatre::factory()->create(['adapter' => 'csv']);
    $production = Production::factory()->for($theatre)->create(['author' => null]);
    Performance::factory()->for($production)->create(['starts_at' => now()->addDays(3)]);
    Credit::create(['production_id' => $production->id, 'person_id' => Person::factory()->create()->id, 'role_type' => CreditRole::Actor]);
    AdapterRun::create(['theatre_id' => $theatre->id, 'adapter' => 'csv', 'status' => 'failed', 'error' => 'boom', 'started_at' => now(), 'finished_at' => now()]);
    Review::create(['user_id' => User::factory()->create()->id, 'production_id' => $production->id, 'body' => 'Чудесно']);
});

it('keeps non-admins out', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('shows the dashboard with sync health and missing data', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk()
        ->assertSee('Синхронизация по театри')
        ->assertSee('Липсващи данни');
});

it('loads every resource list', function (string $path) {
    $this->actingAs(User::factory()->admin()->create())->get($path)->assertOk();
})->with([
    '/admin/theatres',
    '/admin/productions',
    '/admin/productions/create',
    '/admin/production-reviews',
    '/admin/people',
    '/admin/reviews',
    '/admin/adapter-runs',
    '/admin/users',
]);

it('opens a production for editing', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/productions/'.Production::first()->id.'/edit')
        ->assertOk();
});
