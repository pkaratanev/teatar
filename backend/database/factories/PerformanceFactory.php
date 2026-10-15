<?php

namespace Database\Factories;

use App\Models\Performance;
use App\Models\Production;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Performance>
 */
class PerformanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'production_id' => Production::factory(),
            'starts_at' => fake()->dateTimeBetween('+1 day', '+30 days')->setTime(19, 0),
            'price_from' => 10,
            'price_to' => 20,
        ];
    }
}
