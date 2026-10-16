<?php

namespace Database\Seeders;

use App\Models\Theatre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The launch set: 12 Sofia theatres (see docs/plan.md) and Varna, the second city. Only names and the
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
            ['name' => 'Младежки театър „Николай Бинев“', 'website' => 'https://mlt.bg', 'adapter' => 'mlt'],
            ['name' => 'Театър 199 „Валентин Стойчев“', 'website' => 'https://theatre199.org', 'adapter' => 'theatre199'],
            ['name' => 'Театрална работилница „Сфумато“'],
            ['name' => 'Сатиричен театър „Алеко Константинов“', 'website' => 'https://satirata.bg', 'adapter' => 'satirata'],
            ['name' => 'Малък градски театър „Зад канала“', 'website' => 'https://zadkanala.bg', 'adapter' => 'zadkanala'],
            ['name' => 'Театър „София“', 'website' => 'https://sofiatheatre.eu', 'adapter' => 'sofia'],
            ['name' => 'Театър „Възраждане“', 'website' => 'https://theatrevazrajdane.bg', 'adapter' => 'vazrajdane'],
            ['name' => 'Нов театър НДК'],
            ['name' => 'Théatro', 'website' => 'https://www.theatro.bg', 'adapter' => 'theatro'],
            ['name' => 'Драматичен театър „Стоян Бъчваров“', 'city' => 'Варна', 'website' => 'https://dramavarna.com', 'adapter' => 'dramavarna'],
            ['name' => 'Държавен куклен театър – Варна', 'city' => 'Варна', 'website' => 'https://vnpuppet.com', 'adapter' => 'vnpuppet'],
        ];

        foreach ($theatres as $t) {
            Theatre::firstOrCreate(
                ['slug' => Str::slug($t['name'])],
                ['name' => $t['name'], 'city' => $t['city'] ?? 'София', 'website' => $t['website'] ?? null, 'adapter' => $t['adapter'] ?? null, 'source' => 'admin'],
            );
        }
    }
}
