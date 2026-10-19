<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

/** The nightly programme sync: time of day (Sofia) and the pause switch, both set in the admin. */
class SyncSchedule
{
    public const DEFAULT_TIME = '03:15';

    public const TIME_PATTERN = '/^([01]\d|2[0-3]):[0-5]\d$/';

    public static function time(): string
    {
        $time = Setting::read('sync.time');

        return $time !== null && preg_match(self::TIME_PATTERN, $time) ? $time : self::DEFAULT_TIME;
    }

    public static function paused(): bool
    {
        return Setting::read('sync.paused') === '1';
    }

    public static function save(string $time, bool $paused): void
    {
        if (! preg_match(self::TIME_PATTERN, $time)) {
            throw new \InvalidArgumentException("Time must be HH:MM, got [{$time}]");
        }

        Setting::write('sync.time', $time);
        Setting::write('sync.paused', $paused ? '1' : '0');
    }

    /** Called from routes/console.php; reads the time when the scheduler boots, so a change applies from the next minute. */
    public static function register(Schedule $schedule): Event
    {
        return $schedule->command('teatar:sync-programmes')
            ->dailyAt(self::time())
            ->timezone((string) config('app.theatre_timezone'))
            ->skip(fn () => self::paused())
            ->description('Нощна синхронизация на програмите');
    }

    /** Null while paused. */
    public static function nextRun(): ?CarbonImmutable
    {
        if (self::paused()) {
            return null;
        }

        $now = CarbonImmutable::now((string) config('app.theatre_timezone'));
        [$hour, $minute] = array_map('intval', explode(':', self::time()));
        $next = $now->setTime($hour, $minute);

        return $next->greaterThan($now) ? $next : $next->addDay();
    }

    public static function summary(): string
    {
        $next = self::nextRun();

        return $next === null
            ? 'Нощната синхронизация е на пауза (ръчното „Обнови“ работи).'
            : 'Нощна синхронизация всеки ден в '.self::time().' · следваща '.$next->format('d.m. \в H:i').'.';
    }
}
