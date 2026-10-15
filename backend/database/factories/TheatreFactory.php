<?php

namespace Database\Factories;

use App\Models\Theatre;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Theatre>
 */
class TheatreFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Театър '.fake()->unique()->lastName();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'city' => 'София',
            'address' => fake()->streetAddress(),
            'website' => fake()->url(),
            'box_office_url' => fake()->url(),
        ];
    }
}
