<?php

namespace Database\Seeders;

use App\Enums\CreditRole;
use App\Models\Credit;
use App\Models\Performance;
use App\Models\Person;
use App\Models\Production;
use App\Models\Theatre;
use Illuminate\Database\Seeder;

/**
 * Fake productions, people and performances so the app has something to show
 * during local development. Never run in production.
 */
class DemoCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $people = Person::factory(40)->create();

        Theatre::all()->each(function (Theatre $theatre) use ($people) {
            Production::factory(4)->for($theatre)->create(['source' => 'demo'])->each(function (Production $production) use ($people) {
                Credit::create(['production_id' => $production->id, 'person_id' => $people->random()->id, 'role_type' => CreditRole::Director]);

                $people->random(5)->values()->each(fn (Person $p, int $i) => Credit::create([
                    'production_id' => $production->id,
                    'person_id' => $p->id,
                    'role_type' => CreditRole::Actor,
                    'character_name' => fake()->firstName(),
                    'sort' => $i + 1,
                ]));

                foreach (range(1, 3) as $n) {
                    Performance::factory()->for($production)->create([
                        'starts_at' => now()->addDays($n * 7 + fake()->numberBetween(0, 6))->setTime(fake()->randomElement([16, 19]), 0),
                        'source' => 'demo',
                    ]);
                }
            });
        });
    }
}
