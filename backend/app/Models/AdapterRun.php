<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One programme sync of one theatre. Status: queued → running → succeeded | failed.
 *
 * @property string $status
 * @property int $performances_found
 * @property string|null $error
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 */
#[Fillable(['theatre_id', 'adapter', 'status', 'performances_found', 'performances_upserted', 'performances_moved', 'performances_cancelled', 'productions_created', 'error', 'started_at', 'finished_at'])]
class AdapterRun extends Model
{
    /** A run still "running" after this long died (worker killed, server restart). */
    public const STUCK_RUNNING_MINUTES = 30;

    /** A run still "queued" after this long was never picked up: no queue worker is running. */
    public const STUCK_QUEUED_MINUTES = 120;

    public const BUSY = ['queued', 'running'];

    public const LABELS = [
        'queued' => 'На опашка',
        'running' => 'Тече',
        'succeeded' => 'OK',
        'failed' => 'Грешка',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Theatre, $this> */
    public function theatre(): BelongsTo
    {
        return $this->belongsTo(Theatre::class);
    }

    /** Mark runs that will never finish as failed, so nothing shows "running" forever. Returns how many. */
    public static function markStale(): int
    {
        $running = static::query()
            ->where('status', 'running')
            ->where('started_at', '<', now()->subMinutes(self::STUCK_RUNNING_MINUTES))
            ->update([
                'status' => 'failed',
                'error' => 'Прекъснато: не завърши за '.self::STUCK_RUNNING_MINUTES.' минути.',
                'finished_at' => now(),
            ]);

        $queued = static::query()
            ->where('status', 'queued')
            ->where('started_at', '<', now()->subMinutes(self::STUCK_QUEUED_MINUTES))
            ->update([
                'status' => 'failed',
                'error' => 'Не стартира: няма работещ queue worker (php artisan queue:work).',
                'finished_at' => now(),
            ]);

        return $running + $queued;
    }

    public static function color(?string $status): string
    {
        return match ($status) {
            'succeeded' => 'success',
            'failed' => 'danger',
            'running', 'queued' => 'info',
            default => 'gray',
        };
    }
}
