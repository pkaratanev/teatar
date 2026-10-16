<?php

namespace App\Services;

use App\Models\Follow;
use App\Models\Person;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Following users and people, and liking reviews. */
class Social
{
    public function follow(User $follower, User|Person $target): void
    {
        if ($target instanceof User && $target->is($follower)) {
            throw new InvalidArgumentException('Users cannot follow themselves.');
        }

        Follow::firstOrCreate([
            'user_id' => $follower->id,
            'followable_type' => $target->getMorphClass(),
            'followable_id' => $target->getKey(),
        ]);
    }

    public function unfollow(User $follower, User|Person $target): void
    {
        Follow::where('user_id', $follower->id)
            ->where('followable_type', $target->getMorphClass())
            ->where('followable_id', $target->getKey())
            ->delete();
    }

    public function isFollowing(?User $follower, Model $target): bool
    {
        return $follower !== null && Follow::where('user_id', $follower->id)
            ->where('followable_type', $target->getMorphClass())
            ->where('followable_id', $target->getKey())
            ->exists();
    }

    public function followersCount(Model $target): int
    {
        return Follow::where('followable_type', $target->getMorphClass())->where('followable_id', $target->getKey())->count();
    }

    public function followingCount(User $user): int
    {
        return Follow::where('user_id', $user->id)->where('followable_type', (new User)->getMorphClass())->count();
    }

    /** Likes are counted from the table, so the number can't drift. */
    public function like(User $user, Review $review, bool $on): Review
    {
        DB::transaction(function () use ($user, $review, $on) {
            $on
                ? ReviewLike::firstOrCreate(['user_id' => $user->id, 'review_id' => $review->id])
                : ReviewLike::where('user_id', $user->id)->where('review_id', $review->id)->delete();

            $review->forceFill(['likes_count' => ReviewLike::where('review_id', $review->id)->count()])->saveQuietly();
        });

        return $review;
    }
}
