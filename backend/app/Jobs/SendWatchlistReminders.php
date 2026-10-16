<?php

namespace App\Jobs;

use App\Models\Performance;
use App\Models\WatchlistItem;
use App\Services\ExpoPush;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * "Искам да гледам" reminders: when a watchlisted production has a date in
 * the next N days, tell the user once. A new reminder is allowed only after
 * the previous one is three weeks old, so a long run doesn't nag.
 */
class SendWatchlistReminders implements ShouldQueue
{
    use Queueable;

    public function handle(ExpoPush $push): void
    {
        $days = (int) config('teatar.watchlist_reminder_days', 7);
        $tz = config('app.theatre_timezone');

        WatchlistItem::query()
            ->with(['user', 'production.theatre'])
            ->where(fn ($q) => $q->whereNull('reminded_at')->orWhere('reminded_at', '<', now()->subWeeks(3)))
            ->whereHas('user', fn ($q) => $q->whereNotNull('expo_push_token'))
            ->whereHas('production.performances', fn ($q) => $q->where('starts_at', '>=', now())
                ->where('starts_at', '<=', now()->addDays($days))
                ->where('is_cancelled', false))
            ->chunkById(200, function ($items) use ($push, $days, $tz) {
                foreach ($items as $item) {
                    $next = Performance::where('production_id', $item->production_id)
                        ->where('starts_at', '>=', now())
                        ->where('starts_at', '<=', now()->addDays($days))
                        ->where('is_cancelled', false)
                        ->orderBy('starts_at')
                        ->first();
                    if (! $next) {
                        continue;
                    }

                    $local = $next->starts_at->setTimezone($tz)->locale('bg');
                    $when = $local->translatedFormat('l, j F').' в '.$local->format('H:i');
                    $sent = $push->send(
                        (string) $item->user->expo_push_token,
                        $item->production->title,
                        "Играе {$when} в {$item->production->theatre->name}.",
                        ['productionId' => $item->production_id],
                    );

                    if ($sent) {
                        $item->update(['reminded_at' => now()]);
                    }
                }
            });
    }
}
