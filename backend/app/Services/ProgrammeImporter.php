<?php

namespace App\Services;

use App\Adapters\ScrapedPerformance;
use App\Enums\CatalogueSource;
use App\Enums\ProductionStatus;
use App\Models\Performance;
use App\Models\Production;
use App\Models\Stage;
use App\Models\Theatre;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns an adapter's raw performances into catalogue rows. Idempotent:
 * running it twice on the same input changes nothing the second time.
 */
class ProgrammeImporter
{
    /**
     * @param  iterable<ScrapedPerformance>  $scraped
     * @return array{found: int, upserted: int, productions_created: int}
     */
    public function import(Theatre $theatre, iterable $scraped): array
    {
        $stats = ['found' => 0, 'upserted' => 0, 'productions_created' => 0];

        DB::transaction(function () use ($theatre, $scraped, &$stats) {
            foreach ($scraped as $item) {
                $stats['found']++;

                $production = $this->findProduction($theatre, $item->title);
                if ($production === null) {
                    $production = $this->createProduction($theatre, $item);
                    $stats['productions_created']++;
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

        return $stats;
    }

    /** Titles differ in case and quotes between sources ("ГНЯВ" vs „Гняв“), so match on a normalised form. */
    public static function normaliseTitle(string $title): string
    {
        $title = preg_replace('/["\'„“”«»‚‘’]/u', '', $title);
        $title = preg_replace('/\s+/u', ' ', $title);

        return mb_strtolower(trim($title));
    }

    private function findProduction(Theatre $theatre, string $title): ?Production
    {
        $wanted = self::normaliseTitle($title);

        return $theatre->productions()
            ->where('status', ProductionStatus::Running)
            ->get(['id', 'title'])
            ->first(fn (Production $p) => self::normaliseTitle($p->title) === $wanted);
    }

    private function createProduction(Theatre $theatre, ScrapedPerformance $item): Production
    {
        $title = trim(preg_replace('/^["„“]+|["“”]+$/u', '', $item->title));

        // Adapters only know the title. Cast, author and synopsis are filled in Filament
        // or by the community; the admin dashboard lists productions missing them.
        return $theatre->productions()->create([
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'status' => ProductionStatus::Running,
            'source' => CatalogueSource::Adapter->value,
            'source_url' => $item->sourceUrl ?? $theatre->website,
        ]);
    }
}
