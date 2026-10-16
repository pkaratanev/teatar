<?php

namespace App\Services;

use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Theatre;
use Illuminate\Database\Eloquent\Builder;

/**
 * Starts programme syncs: the nightly command, "Обнови всички" and the per-theatre buttons all
 * come through here, so a theatre that is already queued or running is never synced twice.
 */
class ProgrammeSync
{
    /** @return Builder<Theatre> active theatres with an adapter: the set the nightly sync covers */
    public static function theatres(): Builder
    {
        return Theatre::query()->where('is_active', true)->whereNotNull('adapter');
    }

    public function isBusy(Theatre $theatre): bool
    {
        AdapterRun::markStale();

        return $theatre->adapterRuns()->whereIn('status', AdapterRun::BUSY)->exists();
    }

    /** Queue one theatre; null when it is already queued or running. */
    public function queue(Theatre $theatre): ?AdapterRun
    {
        if ($this->isBusy($theatre)) {
            return null;
        }

        $run = AdapterRun::create([
            'theatre_id' => $theatre->id,
            'adapter' => (string) $theatre->adapter,
            'status' => 'queued',
            'started_at' => now(),
        ]);

        SyncTheatreProgramme::dispatch($theatre, $run->id);

        return $run;
    }

    /** @return array{queued: list<string>, skipped: list<string>} theatre names */
    public function queueAll(): array
    {
        $result = ['queued' => [], 'skipped' => []];

        foreach (self::theatres()->orderBy('name')->get() as $theatre) {
            $result[$this->queue($theatre) ? 'queued' : 'skipped'][] = $theatre->name;
        }

        return $result;
    }
}
