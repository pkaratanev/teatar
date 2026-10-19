<?php

use App\Models\AdapterRun;
use App\Models\Theatre;
use Illuminate\Support\Facades\Queue;

it('picks one theatre by its adapter or its slug', function () {
    Queue::fake();
    $zad = Theatre::factory()->create(['adapter' => 'zadkanala', 'slug' => 'malk-gradski-teatr-zad-kanala']);
    Theatre::factory()->create(['adapter' => 'theatro']);

    $this->artisan('teatar:sync-programmes', ['theatre' => 'zadkanala'])->expectsOutputToContain('Queued')->assertSuccessful();
    expect(AdapterRun::where('theatre_id', $zad->id)->count())->toBe(1)->and(AdapterRun::count())->toBe(1);

    AdapterRun::query()->delete();
    $this->artisan('teatar:sync-programmes', ['theatre' => 'malk-gradski-teatr-zad-kanala'])->assertSuccessful();
    expect(AdapterRun::where('theatre_id', $zad->id)->count())->toBe(1);

    $this->artisan('teatar:sync-programmes', ['theatre' => 'nope'])->expectsOutputToContain('zadkanala')->assertSuccessful();
});
