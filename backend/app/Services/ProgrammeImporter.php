<?php

namespace App\Services;

use App\Adapters\ScrapedPerformance;
use App\Enums\CatalogueSource;
use App\Enums\CreditRole;
use App\Enums\ProductionStatus;
use App\Models\Performance;
use App\Models\Person;
use App\Models\Production;
use App\Models\Stage;
use App\Models\Theatre;
use App\Support\Cyrillic;
use App\Support\TheatreHttp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Turns an adapter's raw performances into catalogue rows. Idempotent:
 * running it twice on the same input changes nothing the second time.
 */
class ProgrammeImporter
{
    /** Posters bigger than this are skipped; theatre sites sometimes link print-size files. */
    public const MAX_POSTER_BYTES = 8 * 1024 * 1024;

    /**
     * @param  iterable<ScrapedPerformance>  $scraped
     * @return array{found: int, upserted: int, productions_created: int, posters: int}
     */
    public function import(Theatre $theatre, iterable $scraped): array
    {
        $stats = ['found' => 0, 'upserted' => 0, 'productions_created' => 0, 'posters' => 0];
        /** @var array<int, array{Production, string}> $posters production id → [production, poster url] */
        $posters = [];

        DB::transaction(function () use ($theatre, $scraped, &$stats, &$posters) {
            foreach ($scraped as $item) {
                $stats['found']++;

                $production = $this->findProduction($theatre, $item->title, $item->guest(), $item->visitingCompany);
                if ($production === null) {
                    $production = $this->createProduction($theatre, $item);
                    $stats['productions_created']++;
                } else {
                    // Fill gaps, never overwrite what an admin already set.
                    $gaps = array_filter([
                        'author' => $production->author === null ? $item->author : null,
                        'age_advisory' => $production->age_advisory === null ? $item->ageAdvisory : null,
                    ]);
                    if ($gaps !== []) {
                        $production->update($gaps);
                    }
                }

                if ($item->director !== null) {
                    $this->ensureDirector($production, $item->director);
                }

                // Only for productions without one, so an admin's upload is never replaced.
                if ($item->posterUrl !== null && ! isset($posters[$production->id]) && ! $production->hasMedia('poster')) {
                    $posters[$production->id] = [$production, $item->posterUrl];
                }

                $stage = $item->stage
                    ? Stage::firstOrCreate(['theatre_id' => $theatre->id, 'name' => $item->stage])
                    : null;

                $performance = Performance::updateOrCreate(
                    ['production_id' => $production->id, 'starts_at' => $item->startsAt->utc()],
                    [
                        'stage_id' => $stage?->id,
                        'ticket_url' => $item->ticketUrl,
                        'price_from' => $item->priceFrom,
                        'price_to' => $item->priceTo,
                        'is_cancelled' => false,
                        'source' => CatalogueSource::Adapter->value,
                        'source_url' => $item->sourceUrl ?? $theatre->website,
                    ],
                );

                if ($performance->wasRecentlyCreated || $performance->wasChanged()) {
                    $stats['upserted']++;
                }
            }
        });

        // Downloads happen after the transaction, so a slow image server doesn't hold the database.
        foreach ($posters as [$production, $url]) {
            $stats['posters'] += (int) $this->attachPoster($production, $url);
        }

        return $stats;
    }

    /**
     * The theatre's own poster, stored once with its source URL. A failed download is logged and
     * skipped: a missing poster must never fail the programme import.
     */
    private function attachPoster(Production $production, string $url): bool
    {
        try {
            $response = TheatreHttp::client()->accept('image/*')->get($url);
            $type = (string) $response->header('Content-Type');
            $body = $response->body();

            if (! $response->successful() || ! str_starts_with($type, 'image/') || $body === '' || strlen($body) > self::MAX_POSTER_BYTES) {
                Log::info("Poster skipped for production {$production->id}: {$url} (HTTP {$response->status()}, {$type}, ".strlen($body).' bytes)');

                return false;
            }

            $extension = match (true) {
                str_contains($type, 'png') => 'png',
                str_contains($type, 'webp') => 'webp',
                default => 'jpg',
            };

            $production->addMediaFromString($body)
                ->usingFileName("{$production->slug}.{$extension}")
                ->withCustomProperties(['source_url' => $url])
                ->toMediaCollection('poster');

            return true;
        } catch (Throwable $e) {
            Log::warning("Poster failed for production {$production->id}: {$url}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Tidy a title as it appears on a programme: fold Latin look-alikes, collapse
     * spaces, and drop quotes only when they wrap the whole title — so
     * "Гняв" becomes Гняв but Лекция за "СИЛАТА НА СЛОВОТО" keeps its closing quote.
     */
    public static function cleanTitle(string $title): string
    {
        $title = Cyrillic::foldHomoglyphs($title);
        $title = trim(preg_replace('/\s+/u', ' ', $title) ?? $title);

        if (preg_match('/^["„“”](.+)["“”]$/u', $title, $m) && ! preg_match('/["„“”]/u', $m[1])) {
            $title = trim($m[1]);
        }

        return $title;
    }

    /** Titles differ in case and quotes between sources ("ГНЯВ" vs „Гняв“), so match on a normalised form. */
    public static function normaliseTitle(string $title): string
    {
        $title = Cyrillic::foldHomoglyphs($title);
        $title = preg_replace('/["\'„“”«»‚‘’]/u', '', $title);
        $title = preg_replace('/\s+/u', ' ', $title);

        return mb_strtolower(trim($title));
    }

    private function findProduction(Theatre $theatre, string $title, bool $guest, ?string $visitingCompany): ?Production
    {
        $wanted = self::normaliseTitle($title);
        $company = $visitingCompany === null ? null : self::normaliseTitle($visitingCompany);

        // The same title as a home and a guest production, or by a different
        // visiting company, is a different production.
        return $theatre->productions()
            ->where('status', ProductionStatus::Running)
            ->where('is_guest', $guest)
            ->get(['id', 'title', 'author', 'age_advisory', 'is_guest', 'visiting_company'])
            ->first(fn (Production $p) => self::normaliseTitle($p->title) === $wanted
                && ($p->visiting_company === null ? null : self::normaliseTitle($p->visiting_company)) === $company);
    }

    /** Add a director credit when the production has none yet; reuse a person with the same name. */
    private function ensureDirector(Production $production, string $name): void
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        if ($name === '' || $production->credits()->where('role_type', CreditRole::Director)->exists()) {
            return;
        }

        $person = Person::where('name', $name)->first()
            ?? Person::whereRaw('lower(name) = ?', [mb_strtolower($name)])->first()
            ?? Person::create([
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
                'source' => CatalogueSource::Adapter->value,
                'source_url' => $production->source_url,
            ]);

        $production->credits()->create(['person_id' => $person->id, 'role_type' => CreditRole::Director]);
    }

    private function createProduction(Theatre $theatre, ScrapedPerformance $item): Production
    {
        $title = self::cleanTitle($item->title);

        // Adapters only know the title. Cast, author and synopsis are filled in Filament
        // or by the community; the admin dashboard lists productions missing them.
        return $theatre->productions()->create([
            'title' => $title,
            'author' => $item->author,
            'age_advisory' => $item->ageAdvisory,
            'is_guest' => $item->guest(),
            'visiting_company' => $item->visitingCompany,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'status' => ProductionStatus::Running,
            'source' => CatalogueSource::Adapter->value,
            'source_url' => $item->sourceUrl ?? $theatre->website,
        ]);
    }
}
