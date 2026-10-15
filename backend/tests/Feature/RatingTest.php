<?php

use App\Models\Production;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\QueryException;

it('keeps the production average in stars up to date', function () {
    $production = Production::factory()->create();

    Rating::create(['user_id' => User::factory()->create()->id, 'production_id' => $production->id, 'half_stars' => 10]);
    Rating::create(['user_id' => User::factory()->create()->id, 'production_id' => $production->id, 'half_stars' => 7]);

    expect($production->fresh())
        ->ratings_count->toBe(2)
        ->rating_average->toBe(4.25);
});

it('allows only one rating per user per production', function () {
    $production = Production::factory()->create();
    $user = User::factory()->create();

    Rating::create(['user_id' => $user->id, 'production_id' => $production->id, 'half_stars' => 6]);
    Rating::create(['user_id' => $user->id, 'production_id' => $production->id, 'half_stars' => 8]);
})->throws(QueryException::class);
