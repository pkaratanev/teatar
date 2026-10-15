<?php

namespace Database\Factories;

use App\Enums\ProductionStatus;
use App\Models\Production;
use App\Models\Theatre;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Production>
 */
class ProductionFactory extends Factory
{
    public function definition(): array
    {
        $title = Str::title(fake()->words(3, true));

        return [
            'theatre_id' => Theatre::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'author' => fake()->name(),
            'synopsis' => fake()->paragraph(),
            'age_advisory' => '12+',
            'running_time_minutes' => fake()->numberBetween(60, 180),
            'premiered_on' => fake()->dateTimeBetween('-3 years', '-1 month'),
            'status' => ProductionStatus::Running,
        ];
    }
}
