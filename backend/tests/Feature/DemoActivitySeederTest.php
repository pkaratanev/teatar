<?php

use App\Models\DiaryEntry;
use App\Models\Follow;
use App\Models\Performance;
use App\Models\Rating;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DemoActivitySeeder;
use Database\Seeders\DemoCatalogueSeeder;
use Database\Seeders\LaunchTheatresSeeder;

it('fills every screen with demo activity, including the admin account', function () {
    $this->seed([LaunchTheatresSeeder::class, DemoCatalogueSeeder::class]);
    $admin = User::factory()->create(['email' => 'admin@teatar.test', 'username' => 'admin']);

    $this->seed(DemoActivitySeeder::class);

    expect(User::where('email', 'like', '%@demo.teatar.test')->count())->toBe(12)
        ->and(DiaryEntry::count())->toBeGreaterThan(40)
        ->and(Rating::count())->toBeGreaterThan(40)
        ->and(Review::count())->toBeGreaterThan(10)
        ->and(Review::where('has_spoilers', true)->exists())->toBeTrue()
        ->and(Follow::where('user_id', $admin->id)->count())->toBe(8)
        ->and(DiaryEntry::where('user_id', $admin->id)->count())->toBe(8);

    // Admin's feed has reviews in it.
    $this->actingAs($admin, 'sanctum')->getJson('/api/v1/me/feed')->assertOk()->assertJson(fn ($j) => $j->has('data.0')->etc());
});

it('can run twice without duplicating people', function () {
    $this->seed([LaunchTheatresSeeder::class, DemoCatalogueSeeder::class, DemoActivitySeeder::class, DemoActivitySeeder::class]);

    expect(User::where('email', 'like', '%@demo.teatar.test')->count())->toBe(12);
});

it('does nothing without productions', function () {
    $this->seed(DemoActivitySeeder::class);

    expect(User::count())->toBe(0)->and(Performance::count())->toBe(0);
});

it('runs the one-command offline setup', function () {
    $this->artisan('teatar:demo', ['--offline' => true])->assertSuccessful();

    expect(User::where('email', 'admin@teatar.test')->value('is_admin'))->toBeTrue()
        ->and(Performance::count())->toBeGreaterThan(0)
        ->and(Review::count())->toBeGreaterThan(0);
});
