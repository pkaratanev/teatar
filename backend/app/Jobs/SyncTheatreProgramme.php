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

    /** Seconds before the worker kills the job; the queue's retry_after must be longer (config/queue.php). */
    public int $timeout = 600;

    /** @param  int|null  $runId  the "queued" run made by ProgrammeSync; null makes a new one (teatar:demo, tests) */
    public function __construct(public Theatre $theatre, public ?int $runId = null) {}

    public function handle(ProgrammeImporter $importer): void
    {
        $values = ['status' => 'running', 'started_at' => now(), 'finished_at' => null, 'error' => null];
        $run = $this->runId !== null ? AdapterRun::find($this->runId) : null;

        if ($run !== null) {
            $run->update($values);
        } else {
            $run = AdapterRun::create([
                'theatre_id' => $this->theatre->id,
                'adapter' => (string) $this->theatre->adapter,
                ...$values,
            ]);
            $this->runId = $run->id;
        }

        try {
            $stats = $importer->import($this->theatre, self::resolveAdapter($this->theatre)->fetch($this->theatre));

            $run->update([
                'status' => 'succeeded',
                'performances_found' => $stats['found'],
                'performances_upserted' => $stats['upserted'],
                'productions_created' => $stats['productions_created'],
                'performances_moved' => $stats['moved'],
                'performances_cancelled' => $stats['cancelled'],
                'error' => $stats['held_back'] > 0
                    ? "Не са отменени {$stats['held_back']} представления, които източникът вече не показва: твърде много наведнъж. Провери сайта."
                    : null,
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

    /** Timeout or a killed worker: handle() never got to its catch, so close the run here. */
    public function failed(?Throwable $e): void
    {
        if ($this->runId === null) {
            return;
        }

        AdapterRun::whereKey($this->runId)->whereIn('status', AdapterRun::BUSY)->update([
            'status' => 'failed',
            'error' => mb_substr('Прекъснато: '.($e?->getMessage() ?? 'неизвестна причина'), 0, 2000),
            'finished_at' => now(),
        ]);
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
