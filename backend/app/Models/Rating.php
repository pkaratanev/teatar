<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'production_id', 'half_stars'])]
class Rating extends Model
{
    protected static function booted(): void
    {
        static::saved(fn (Rating $rating) => $rating->production->refreshRatingStats());
        static::deleted(fn (Rating $rating) => $rating->production->refreshRatingStats());
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Production, $this> */
    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    public function stars(): float
    {
        return $this->half_stars / 2;
    }
}
