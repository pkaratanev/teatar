<?php

namespace Database\Seeders;

use App\Models\Theatre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The proposed Sofia launch set (see docs/plan.md). Only names and the
 * websites we have confirmed; addresses, box-office links and adapters are
 * filled in Filament as each theatre is onboarded.
 */
class LaunchTheatresSeeder extends Seeder
{
    public function run(): void
    {
        $theatres = [
            ['name' => 'Театър „Българска армия“', 'website' => 'http://www.tba.art.bg', 'adapter' => 'tba'],
            ['name' => 'Народен театър „Иван Вазов“', 'website' => 'https://nationaltheatre.bg', 'adapter' => 'national'],
            ['name' => 'Открита сцена „Сълза и смях“', 'website' => 'https://www.salzaismyah.bg', 'adapter' => 'salzaismyah'],
            ['name' => 'Младежки театър „Николай Бинев“'],
            ['name' => 'Театър 199 „Валентин Стойчев“'],
            ['name' => 'Театрална работилница „Сфумато“'],
            ['name' => 'Сатиричен театър „Алеко Константинов“'],
            ['name' => 'Малък градски театър „Зад канала“', 'website' => 'https://zadkanala.bg', 'adapter' => 'zadkanala'],
            ['name' => 'Театър „София“', 'website' => 'https://sofiatheatre.eu', 'adapter' => 'sofia'],
            ['name' => 'Театър „Възраждане“', 'website' => 'https://theatrevazrajdane.bg', 'adapter' => 'vazrajdane'],
            ['name' => 'Нов театър НДК'],
            ['name' => 'Théatro', 'website' => 'https://www.theatro.bg', 'adapter' => 'theatro'],
        ];

        foreach ($theatres as $t) {
            Theatre::firstOrCreate(
                ['slug' => Str::slug($t['name'])],
                ['name' => $t['name'], 'city' => 'София', 'website' => $t['website'] ?? null, 'adapter' => $t['adapter'] ?? null, 'source' => 'admin'],
            );
        }
    }
}
