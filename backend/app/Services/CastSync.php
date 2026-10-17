<?php

namespace App\Services;

use App\Adapters\Cast\CastReader;
use App\Enums\CatalogueSource;
use App\Enums\CreditRole;
use App\Enums\ProductionStatus;
use App\Models\Production;
use App\Models\Theatre;
use App\Support\Encoding;
use App\Support\TheatreHttp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * After a sync: reads the team and cast from the theatre's own page of each production
 * that has no actors yet. A page is read once; one that had nothing is tried again after
 * CHECK_AGAIN_DAYS. Credits are only added, never removed, and the production goes on the
 * review list when something was added.
 */
class CastSync
{
    public const CHECK_AGAIN_DAYS = 30;

    public function __construct(private Credits $credits) {}

    /** @return int credits added */
    public function fill(Theatre $theatre): int
    {
        $reader = self::reader($theatre);
        $limit = (int) config('teatar.cast_reader.per_run', 25);
        if ($reader === null || $limit <= 0) {
            return 0;
        }

        $productions = $theatre->productions()
            ->where('status', ProductionStatus::Running)
            ->whereNotNull('source_url')
            ->whereDoesntHave('credits', fn ($q) => $q->where('role_type', CreditRole::Actor))
            ->where(fn ($q) => $q->whereNull('credits_checked_at')->orWhere('credits_checked_at', '<', now()->subDays(self::CHECK_AGAIN_DAYS)))
            ->orderBy('id')
            ->limit($limit * 2) // some are skipped below
            ->get()
            ->filter(fn (Production $p) => $reader->accepts((string) $p->source_url))
            ->take($limit);

        $added = 0;
        foreach ($productions->values() as $i => $production) {
            if ($i > 0) {
                Sleep::for($reader->delaySeconds())->seconds();
            }
            $added += $this->fillOne($production, $reader);
        }

        return $added;
    }

    private function fillOne(Production $production, CastReader $reader): int
    {
        $url = (string) $production->source_url;
        $added = 0;

        try {
            $response = TheatreHttp::client()->get($url);
            if ($response->successful()) {
                $parsed = $this->credits->fromPairs($reader->read(Encoding::toUtf8($response->body())));
                $added = $this->credits->add($production, $parsed, CatalogueSource::Adapter, $url);
                if ($added > 0) {
                    $production->flagForReview();
                }
            } else {
                Log::info("Cast page {$url} answered HTTP {$response->status()}");
            }
        } catch (Throwable $e) {
            Log::warning("Cast page {$url} failed: {$e->getMessage()}");
        }

        $production->forceFill(['credits_checked_at' => now()])->save();

        return $added;
    }

    public static function reader(Theatre $theatre): ?CastReader
    {
        $class = config("teatar.cast_reader.readers.{$theatre->adapter}");

        return is_string($class) && is_subclass_of($class, CastReader::class) ? app($class) : null;
    }
}
