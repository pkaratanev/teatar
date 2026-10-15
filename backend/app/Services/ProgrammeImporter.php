<?php

namespace App\Services;

use App\Adapters\ScrapedPerformance;
use App\Enums\CatalogueSource;
use App\Enums\ProductionStatus;
use App\Models\Performance;
use App\Models\Production;
use App\Models\Stage;
use App\Models\Theatre;
use App\Support\Cyrillic;
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

                $production = $this->findProduction($theatre, $item->title, $item->guest(), $item->visitingCompany);
                if ($production === null) {
                    $production = $this->createProduction($theatre, $item);
                    $stats['productions_created']++;
                } elseif ($production->author === null && $item->author !== null) {
                    $production->update(['author' => $item->author]); // fill gaps, never overwrite admin edits
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
            ->get(['id', 'title', 'author', 'is_guest', 'visiting_company'])
            ->first(fn (Production $p) => self::normaliseTitle($p->title) === $wanted
                && ($p->visiting_company === null ? null : self::normaliseTitle($p->visiting_company)) === $company);
    }

    private function createProduction(Theatre $theatre, ScrapedPerformance $item): Production
    {
        $title = self::cleanTitle($item->title);

        // Adapters only know the title. Cast, author and synopsis are filled in Filament
        // or by the community; the admin dashboard lists productions missing them.
        return $theatre->productions()->create([
            'title' => $title,
            'author' => $item->author,
            'is_guest' => $item->guest(),
            'visiting_company' => $item->visitingCompany,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'status' => ProductionStatus::Running,
            'source' => CatalogueSource::Adapter->value,
            'source_url' => $item->sourceUrl ?? $theatre->website,
        ]);
    }
}
