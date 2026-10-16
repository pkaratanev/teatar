<?php

namespace Database\Seeders;

use App\Models\DiaryEntry;
use App\Models\Production;
use App\Models\Rating;
use App\Models\Review;
use App\Models\User;
use App\Models\WatchlistItem;
use App\Services\Social;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Demo people doing theatre things, so every screen has content while testing:
 * ratings, reviews (some with spoilers), diary visits, watchlists, follows
 * and likes. Uses whatever productions exist (real synced ones or the demo
 * catalogue). All demo accounts use @demo.teatar.test emails and the
 * password "password". Never runs in production.
 */
class DemoActivitySeeder extends Seeder
{
    private const PEOPLE = [
        ['Мария Петрова', 'maria.p'], ['Георги Иванов', 'gogo'], ['Елена Димитрова', 'elena_d'],
        ['Николай Стоянов', 'niki.st'], ['Ива Колева', 'iva_k'], ['Димитър Георгиев', 'mitko'],
        ['Радост Василева', 'radost'], ['Петър Николов', 'petar.n'], ['Цвета Ангелова', 'tsveta'],
        ['Кирил Тодоров', 'kiro'], ['Вяра Христова', 'vyara'], ['Стефан Маринов', 'stefan.m'],
    ];

    /** Short demo review texts, keyed by the rating band they fit. */
    private const REVIEWS = [
        'high' => [
            'Една от най-добрите постановки, които съм гледал/а този сезон. Актьорите са невероятни.',
            'Излязох от залата с усмивка. Препоръчвам на всички!',
            'Силен текст, силна режисура. Финалът ме разплака.',
            'Сценографията е прекрасна, а музиката държи напрежението до края.',
        ],
        'mid' => [
            'Добро представление, макар че второто действие е малко дълго.',
            'Хубави актьорски изпълнения, но очаквах повече от режисурата.',
            'Приятна вечер в театъра, нищо повече.',
        ],
        'low' => [
            'Не ме грабна. Историята се влачи, а героите остават далечни.',
            'Очаквах комедия, а беше по-скоро скучно.',
        ],
        'spoiler' => [
            'Не очаквах, че накрая бащата е бил съучастник. Цялата първа част изведнъж придобива друг смисъл.',
            'Сцената, в която тя изгаря писмата, е най-силният момент. Финалът със самотния стол на сцената остава в главата ми.',
        ],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoActivitySeeder must never run in production.');
        }

        $productions = Production::query()->whereHas('performances')->orderBy('id')->limit(60)->get();
        if ($productions->isEmpty()) {
            $this->command?->warn('No productions with performances yet. Sync programmes or run DemoCatalogueSeeder first.');

            return;
        }

        mt_srand(2026); // same demo data on every run
        $people = $this->people();
        $social = app(Social::class);

        foreach ($people as $person) {
            $this->theatreLife($person, $productions);
        }

        // Everyone follows a few others; the admin follows most, so its feed is full.
        foreach ($people as $person) {
            foreach ($people->where('id', '!=', $person->id)->shuffle()->take(mt_rand(2, 5)) as $other) {
                $social->follow($person, $other);
            }
        }

        if ($admin = User::where('email', 'admin@teatar.test')->first()) {
            $this->theatreLife($admin, $productions, visits: 8);
            foreach ($people->take(8) as $other) {
                $social->follow($admin, $other);
            }
        }

        // Likes: each review gets a few from followers.
        $reviews = Review::with('user')->whereIn('user_id', $people->pluck('id'))->get();
        foreach ($reviews as $review) {
            foreach ($people->where('id', '!=', $review->user_id)->shuffle()->take(mt_rand(0, 4)) as $fan) {
                $social->like($fan, $review, true);
            }
        }

        $this->command?->info(sprintf('Demo activity: %d people, %d visits, %d ratings, %d reviews.',
            $people->count(), DiaryEntry::count(), Rating::count(), Review::count()));
    }

    /** @return Collection<int, User> */
    private function people(): Collection
    {
        return collect(self::PEOPLE)->map(fn (array $p) => User::firstOrCreate(
            ['email' => "{$p[1]}@demo.teatar.test"],
            ['name' => $p[0], 'username' => $p[1], 'password' => 'password'],
        ));
    }

    /** @param  Collection<int, Production>  $productions */
    private function theatreLife(User $user, Collection $productions, ?int $visits = null): void
    {
        $tz = config('app.theatre_timezone');
        $seen = $productions->shuffle()->take($visits ?? mt_rand(4, 12));

        foreach ($seen as $production) {
            $seenOn = CarbonImmutable::now($tz)->subDays(mt_rand(1, 240));
            DiaryEntry::firstOrCreate(['user_id' => $user->id, 'production_id' => $production->id, 'seen_on' => $seenOn->toDateString()]);

            $halfStars = $this->plausibleRating();
            Rating::updateOrCreate(['user_id' => $user->id, 'production_id' => $production->id], ['half_stars' => $halfStars]);

            if (mt_rand(1, 100) <= 45) {
                [$body, $spoilers] = $this->reviewText($halfStars);
                $review = Review::updateOrCreate(
                    ['user_id' => $user->id, 'production_id' => $production->id],
                    ['body' => $body, 'has_spoilers' => $spoilers],
                );
                // Date the review the day after the visit, so feeds look like real life.
                $review->forceFill(['created_at' => $seenOn->addDay()])->saveQuietly();
            }
        }

        foreach ($productions->diff($seen)->shuffle()->take(mt_rand(1, 4)) as $production) {
            WatchlistItem::firstOrCreate(['user_id' => $user->id, 'production_id' => $production->id]);
        }
    }

    /** Skewed towards 3–4.5 stars, like real audiences. */
    private function plausibleRating(): int
    {
        $weights = [1 => 1, 2 => 1, 3 => 2, 4 => 3, 5 => 5, 6 => 9, 7 => 12, 8 => 14, 9 => 9, 10 => 6];
        $roll = mt_rand(1, array_sum($weights));
        foreach ($weights as $value => $weight) {
            if (($roll -= $weight) <= 0) {
                return $value;
            }
        }

        return 7;
    }

    /** @return array{0: string, 1: bool} */
    private function reviewText(int $halfStars): array
    {
        if (mt_rand(1, 100) <= 15) {
            $pool = self::REVIEWS['spoiler'];

            return [$pool[array_rand($pool)], true];
        }

        $band = $halfStars >= 8 ? 'high' : ($halfStars >= 5 ? 'mid' : 'low');
        $pool = self::REVIEWS[$band];

        return [$pool[array_rand($pool)], false];
    }
}
