<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Models\DiaryEntry;
use App\Models\Production;
use App\Models\Rating;
use App\Models\Review;
use App\Models\User;
use App\Models\WatchlistItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Everything a user records about a production: rating, review, visits and
 * watchlist. Controllers stay thin; the rules live here.
 */
class TheatreLog
{
    public function rate(User $user, Production $production, int $halfStars): Rating
    {
        return Rating::updateOrCreate(
            ['user_id' => $user->id, 'production_id' => $production->id],
            ['half_stars' => $halfStars],
        );
    }

    public function unrate(User $user, Production $production): void
    {
        // Delete through the model so the production's average is recalculated.
        Rating::where('user_id', $user->id)->where('production_id', $production->id)->first()?->delete();
    }

    public function review(User $user, Production $production, string $body, bool $hasSpoilers): Review
    {
        $review = Review::firstOrNew(['user_id' => $user->id, 'production_id' => $production->id]);
        $review->fill(['body' => trim($body), 'has_spoilers' => $hasSpoilers]);
        if (! $review->exists) {
            $review->status = ReviewStatus::Visible;
        }
        $review->save();

        return $review;
    }

    public function unreview(User $user, Production $production): void
    {
        Review::where('user_id', $user->id)->where('production_id', $production->id)->delete();
    }

    /** Seeing a show takes it off the watchlist. */
    public function logVisit(User $user, Production $production, CarbonImmutable $seenOn, ?int $performanceId = null): DiaryEntry
    {
        return DB::transaction(function () use ($user, $production, $seenOn, $performanceId) {
            $entry = DiaryEntry::record($user->id, $production->id, $seenOn->toDateString(), ['performance_id' => $performanceId]);

            $this->unwatch($user, $production);

            return $entry;
        });
    }

    public function watch(User $user, Production $production): WatchlistItem
    {
        return WatchlistItem::firstOrCreate(['user_id' => $user->id, 'production_id' => $production->id]);
    }

    public function unwatch(User $user, Production $production): void
    {
        WatchlistItem::where('user_id', $user->id)->where('production_id', $production->id)->delete();
    }

    /**
     * The signed-in user's own state for one production, shown on its page.
     *
     * @return array{rating: float|null, half_stars: int|null, review: Review|null, seen_on: list<string>, on_watchlist: bool}
     */
    public function stateFor(User $user, Production $production): array
    {
        $halfStars = Rating::where('user_id', $user->id)->where('production_id', $production->id)->value('half_stars');

        return [
            'half_stars' => $halfStars,
            'rating' => $halfStars !== null ? $halfStars / 2 : null,
            'review' => Review::where('user_id', $user->id)->where('production_id', $production->id)->first(),
            'seen_on' => DiaryEntry::where('user_id', $user->id)->where('production_id', $production->id)
                ->orderByDesc('seen_on')->pluck('seen_on')->map(fn ($d) => $d->toDateString())->values()->all(),
            'on_watchlist' => WatchlistItem::where('user_id', $user->id)->where('production_id', $production->id)->exists(),
        ];
    }
}
