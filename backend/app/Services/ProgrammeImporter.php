<?php

namespace App\Services;

use App\Adapters\ScrapedPerformance;
use App\Enums\CatalogueSource;
use App\Enums\CreditRole;
use App\Enums\ProductionStatus;
use App\Models\Performance;
use App\Models\Production;
use App\Models\Stage;
use App\Models\Theatre;
use App\Support\Cyrillic;
use App\Support\TheatreHttp;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
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

    /** Up to this many dropped shows per run are always cancelled; above it, only if they are under half. */
    public const MAX_SAFE_CANCELLATIONS = 5;

    /**
     * @param  iterable<ScrapedPerformance>  $scraped
     * @return array{found: int, upserted: int, productions_created: int, posters: int, moved: int, cancelled: int, held_back: int}
     */
    public function import(Theatre $theatre, iterable $scraped): array
    {
        $stats = ['found' => 0, 'upserted' => 0, 'productions_created' => 0, 'posters' => 0, 'moved' => 0, 'cancelled' => 0, 'held_back' => 0];
        /** @var array<int, array{Production, string}> $posters production id → [production, poster url] */
        $posters = [];

        DB::transaction(function () use ($theatre, $scraped, &$stats, &$posters) {
            /** @var array<int, true> $seen performance ids the source lists this run */
            $seen = [];
            /** @var list<int> $created */
            $created = [];
            $first = $last = null;

            foreach ($scraped as $item) {
                $stats['found']++;
                $first = $first === null || $item->startsAt < $first ? $item->startsAt : $first;
                $last = $last === null || $item->startsAt > $last ? $item->startsAt : $last;

                $production = $this->findProduction($theatre, $item->title, $item->guest(), $item->visitingCompany);
                if ($production === null) {
                    $production = $this->createProduction($theatre, $item);
                    $stats['productions_created']++;
                } else {
                    // Fill gaps, never overwrite what an admin already set.
                    $gaps = array_filter([
                        'author' => $production->author === null ? self::author($item->author) : null,
                        'age_advisory' => $production->age_advisory === null ? $item->ageAdvisory : null,
                    ]);
                    if ($gaps !== []) {
                        $production->update($gaps);
                        $production->flagForReview();
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

                $seen[$performance->id] = true;
                if ($performance->wasRecentlyCreated) {
                    $created[] = $performance->id;
                }
            }

            if ($stats['found'] > 0) {
                $this->reconcile($theatre, $first, $last, $seen, $created, $stats);
            }
        });

        // Downloads happen after the transaction, so a slow image server doesn't hold the database.
        foreach ($posters as [$production, $url]) {
            $stats['posters'] += (int) $this->attachPoster($production, $url);
        }

        return $stats;
    }

    /**
     * Shows the source no longer lists, within the days it covered (first to last listed day, from now on):
     * a moved show keeps its row and id, the rest are marked cancelled. Admin-entered rows are never touched.
     *
     * A move is recognised by a per-show ticket link that now points at a new time, or else by the same
     * production, stage and day with exactly one old and one new time. Anything else is cancel + new row.
     *
     * @param  array<int, true>  $seen
     * @param  list<int>  $created
     * @param  array<string, int>  $stats
     */
    private function reconcile(Theatre $theatre, CarbonInterface $first, CarbonInterface $last, array $seen, array $created, array &$stats): void
    {
        $tz = (string) config('app.theatre_timezone');
        $from = CarbonImmutable::instance($first)->setTimezone($tz)->startOfDay()->utc();
        $from = $from->isBefore(now()) ? now()->toImmutable() : $from;
        $to = CarbonImmutable::instance($last)->setTimezone($tz)->endOfDay()->utc();

        $inWindow = Performance::query()
            ->whereIn('production_id', $theatre->productions()->select('id'))
            ->where('source', CatalogueSource::Adapter->value)
            ->whereBetween('starts_at', [$from, $to]);

        $missing = (clone $inWindow)->where('is_cancelled', false)->whereNotIn('id', array_keys($seen))->get();
        if ($missing->isEmpty()) {
            return;
        }

        $new = Performance::whereIn('id', $created)->get()->keyBy('id');
        $pairs = [];

        // 1. Per-show ticket links (Entase, ePayGO, …): unique on both sides, same production.
        $ticketCount = fn (Collection $rows) => $rows->whereNotNull('ticket_url')->countBy('ticket_url');
        $oldTickets = $ticketCount($missing);
        $newTickets = $ticketCount($new);
        foreach ($missing as $old) {
            $url = $old->ticket_url;
            if ($url === null || ($oldTickets[$url] ?? 0) !== 1 || ($newTickets[$url] ?? 0) !== 1) {
                continue;
            }
            $match = $new->first(fn (Performance $p) => $p->ticket_url === $url && $p->production_id === $old->production_id);
            if ($match !== null) {
                $pairs[$old->id] = [$old, $match];
                $new->forget($match->id);
            }
        }

        // 2. Same production, stage and local day, exactly one old and one new time.
        $key = fn (Performance $p) => $p->production_id.'|'.($p->stage_id ?? '-').'|'.$p->starts_at->copy()->setTimezone($tz)->format('Y-m-d');
        $oldGroups = $missing->reject(fn (Performance $p) => isset($pairs[$p->id]))->groupBy($key);
        $newGroups = $new->groupBy($key);
        foreach ($oldGroups as $group => $olds) {
            if ($olds->count() === 1 && ($newGroups[$group] ?? collect())->count() === 1) {
                $pairs[$olds->first()->id] = [$olds->first(), $newGroups[$group]->first()];
            }
        }

        $toCancel = $missing->reject(fn (Performance $p) => isset($pairs[$p->id]));

        // A source that suddenly lists far fewer shows is more likely a broken page than a mass cancellation.
        $existing = (clone $inWindow)->where('is_cancelled', false)->whereNotIn('id', $created)->count();
        if ($toCancel->count() > self::MAX_SAFE_CANCELLATIONS && $toCancel->count() * 2 > $existing) {
            $stats['held_back'] = $toCancel->count();
            Log::warning("Not cancelling {$toCancel->count()} of {$existing} performances at {$theatre->slug}: the source may be broken.");
            $toCancel = collect();
        }

        foreach ($pairs as [$old, $moved]) {
            $values = $moved->only(['starts_at', 'stage_id', 'ticket_url', 'price_from', 'price_to', 'source_url']);
            $moved->delete(); // Free the (production, start time) key before the old row takes it.
            $old->update([...$values, 'is_cancelled' => false]);
            $stats['moved']++;
        }

        foreach ($toCancel as $old) {
            $old->update(['is_cancelled' => true]);
            $stats['cancelled']++;
        }
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
            $production->flagForReview();

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
            ->get(['id', 'title', 'slug', 'author', 'age_advisory', 'is_guest', 'visiting_company', 'needs_review', 'review_reason'])
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

        $person = app(People::class)->findOrCreate($name, CatalogueSource::Adapter, $production->source_url);

        $production->credits()->create(['person_id' => $person->id, 'role_type' => CreditRole::Director]);
        $production->flagForReview();
    }

    private function createProduction(Theatre $theatre, ScrapedPerformance $item): Production
    {
        $title = self::cleanTitle($item->title);

        // Adapters only know the title. Cast, author and synopsis are filled in Filament
        // or by the community; the admin dashboard lists productions missing them.
        return $theatre->productions()->create([
            'title' => $title,
            'author' => self::author($item->author),
            'age_advisory' => self::ageAdvisory($item->ageAdvisory),
            'is_guest' => $item->guest(),
            'visiting_company' => $item->visitingCompany,
            'slug' => self::slug($title).'-'.Str::lower(Str::random(5)),
            'status' => ProductionStatus::Running,
            'source' => CatalogueSource::Adapter->value,
            'source_url' => $item->sourceUrl ?? $theatre->website,
            'needs_review' => true,
            'review_reason' => 'new',
        ]);
    }

    /** Slug base, short enough for its column (and for poster file names): Cyrillic grows when transliterated. */
    private static function slug(string $text): string
    {
        return rtrim(Str::limit(Str::slug($text), 80, ''), '-') ?: 'x';
    }

    /** The column holds labels like "12+"; anything longer isn't one. */
    private static function ageAdvisory(?string $age): ?string
    {
        $age = $age === null ? null : trim($age);

        return $age !== null && $age !== '' && mb_strlen($age) <= 8 ? $age : null;
    }

    /** "от Франсис Вебер" → "Франсис Вебер": some sites write the author with "от" (by). */
    private static function author(?string $author): ?string
    {
        if ($author === null) {
            return null;
        }
        $author = trim(preg_replace('/^от\s+/iu', '', trim($author)) ?? $author);

        return $author === '' ? null : $author;
    }
}
