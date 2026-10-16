<?php

namespace App\Services;

use App\Models\AdapterRun;
use App\Models\Theatre;
use App\Support\SyncSchedule;
use Illuminate\Support\Str;

/**
 * Theatres whose programme sync needs a look: the last run failed, found nothing,
 * or nothing has run for over a day and a half. Shown on the dashboard and as a badge.
 */
class SyncAlerts
{
    public const STALE_HOURS = 36;

    /** @return list<array{theatre: Theatre, reasons: list<string>}> */
    public static function current(): array
    {
        $alerts = [];

        foreach (ProgrammeSync::theatres()->orderBy('name')->get() as $theatre) {
            $reasons = self::reasons($theatre);
            if ($reasons !== []) {
                $alerts[] = ['theatre' => $theatre, 'reasons' => $reasons];
            }
        }

        return $alerts;
    }

    /** @return list<string> */
    public static function reasons(Theatre $theatre): array
    {
        $reasons = [];

        $latest = $theatre->adapterRuns()->latest('started_at')->first();
        $finished = $theatre->adapterRuns()->whereIn('status', ['succeeded', 'failed'])->latest('started_at')->first();

        if ($latest === null) {
            $reasons[] = 'Никога не е синхронизиран.';
        } elseif ($latest->started_at->lt(now()->subHours(self::STALE_HOURS))) {
            $reasons[] = 'Не е синхронизиран от '.(int) $latest->started_at->diffInHours(now()).' часа'
                .(SyncSchedule::paused() ? ' (нощната синхронизация е на пауза).' : '.');
        }

        if ($finished?->status === 'failed') {
            $reasons[] = 'Последната синхронизация е неуспешна: '.Str::limit((string) $finished->error, 160);
        } elseif ($finished !== null && $finished->performances_found === 0) {
            $reasons[] = 'Последната синхронизация не намери нито едно представление.';
        }

        return $reasons;
    }

    public static function count(): int
    {
        return count(self::current());
    }
}
