<?php

namespace App\Jobs;

use App\Adapters\Contracts\TheatreAdapter;
use App\Models\AdapterRun;
use App\Models\Theatre;
use App\Services\ProgrammeImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use InvalidArgumentException;
use Throwable;

/**
 * Runs one theatre's adapter and records the outcome in adapter_runs, so a
 * broken theatre site shows up in Filament instead of failing silently.
 */
class SyncTheatreProgramme implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Theatre $theatre) {}

    public function handle(ProgrammeImporter $importer): void
    {
        $run = AdapterRun::create([
            'theatre_id' => $this->theatre->id,
            'adapter' => (string) $this->theatre->adapter,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $stats = $importer->import($this->theatre, self::resolveAdapter($this->theatre)->fetch($this->theatre));

            $run->update([
                'status' => 'succeeded',
                'performances_found' => $stats['found'],
                'performances_upserted' => $stats['upserted'],
                'productions_created' => $stats['productions_created'],
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            $run->update([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 2000),
                'finished_at' => now(),
            ]);

            report($e);
        }
    }

    public static function resolveAdapter(Theatre $theatre): TheatreAdapter
    {
        $class = config("teatar.adapters.{$theatre->adapter}");

        if (! $class || ! is_subclass_of($class, TheatreAdapter::class)) {
            throw new InvalidArgumentException("Unknown adapter [{$theatre->adapter}] for theatre {$theatre->slug}");
        }

        return app($class);
    }
}
