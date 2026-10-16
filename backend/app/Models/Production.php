<?php

namespace App\Models;

use App\Enums\ProductionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property int $theatre_id
 * @property string $title
 * @property string $slug
 * @property string|null $author
 * @property string|null $age_advisory
 * @property bool $is_guest
 * @property string|null $visiting_company
 * @property ProductionStatus $status
 * @property \Illuminate\Support\Carbon|null $premiered_on
 * @property int $ratings_count
 * @property float|null $rating_average
 * @property string|null $synopsis
 * @property bool $needs_review
 * @property string|null $review_reason
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property int|null $reviewed_by
 * @property-read Theatre $theatre
 */
#[Fillable(['theatre_id', 'title', 'title_en', 'slug', 'author', 'is_guest', 'visiting_company', 'synopsis', 'age_advisory', 'running_time_minutes', 'premiered_on', 'status', 'source', 'source_url', 'needs_review', 'review_reason', 'reviewed_at', 'reviewed_by'])]
class Production extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'status' => ProductionStatus::class,
            'is_guest' => 'boolean',
            'premiered_on' => 'date',
            'rating_average' => 'float',
            'needs_review' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('poster')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Made right away: local setups run no queue worker, and a queued conversion would never appear.
        $this->addMediaConversion('card')->nonQueued()->width(400)->height(600);
    }

    /** The card-sized poster, or the original while the resized copy doesn't exist. */
    public function posterUrl(): ?string
    {
        $media = $this->getFirstMedia('poster');
        if ($media === null) {
            return null;
        }

        return $media->hasGeneratedConversion('card') ? $media->getUrl('card') : $media->getUrl();
    }

    /** What the review list checks. Labels as shown in the admin. */
    public const CHECKS = ['author' => 'Автор', 'director' => 'Режисьор', 'cast' => 'Актьори', 'poster' => 'Плакат', 'synopsis' => 'Описание'];

    /**
     * Adds has_director, has_cast and has_poster, so missingData() needs no extra queries per row.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithDataChecks(Builder $query): void
    {
        $query->withExists([
            'credits as has_director' => fn (Builder $c) => $c->where('role_type', \App\Enums\CreditRole::Director),
            'credits as has_cast' => fn (Builder $c) => $c->where('role_type', \App\Enums\CreditRole::Actor),
            'media as has_poster' => fn (Builder $m) => $m->where('collection_name', 'poster'),
        ]);
    }

    /** @return list<string> labels of what is missing, e.g. ["Режисьор", "Плакат"] */
    public function missingData(): array
    {
        $has = fn (string $attribute, \Closure $query) => array_key_exists($attribute, $this->getAttributes())
            ? (bool) $this->getAttribute($attribute)
            : $query();

        $present = [
            'author' => filled($this->author),
            'director' => $has('has_director', fn () => $this->credits()->where('role_type', \App\Enums\CreditRole::Director)->exists()),
            'cast' => $has('has_cast', fn () => $this->credits()->where('role_type', \App\Enums\CreditRole::Actor)->exists()),
            'poster' => $has('has_poster', fn () => $this->hasMedia('poster')),
            'synopsis' => filled($this->synopsis),
        ];

        return array_values(array_map(fn (string $key) => self::CHECKS[$key], array_keys(array_filter($present, fn (bool $ok) => ! $ok))));
    }

    /** An importer added or changed something: put it (back) on the review list. A new production stays "new". */
    public function flagForReview(): void
    {
        $reason = $this->needs_review && $this->review_reason === 'new' ? 'new' : 'changed';
        $this->forceFill(['needs_review' => true, 'review_reason' => $reason])->saveQuietly();
    }

    public function markReviewed(?int $userId): void
    {
        $this->forceFill(['needs_review' => false, 'review_reason' => null, 'reviewed_at' => now(), 'reviewed_by' => $userId])->save();
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<Theatre, $this> */
    public function theatre(): BelongsTo
    {
        return $this->belongsTo(Theatre::class);
    }

    /** @return HasMany<Credit, $this> */
    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class)->orderBy('sort');
    }

    /** @return BelongsToMany<Person, $this> */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'credits')->withPivot(['role_type', 'character_name', 'sort']);
    }

    /** @return HasMany<Performance, $this> */
    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class)->orderBy('starts_at');
    }

    /** @return HasMany<Rating, $this> */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** @param Builder<self> $query */
    public function scopeRunning(Builder $query): void
    {
        $query->where('status', ProductionStatus::Running);
    }

    /** Recalculate the denormalised rating columns after a rating changes. */
    public function refreshRatingStats(): void
    {
        $count = $this->ratings()->count();
        $avgHalfStars = $this->ratings()->avg('half_stars');

        $this->forceFill([
            'ratings_count' => $count,
            'rating_average' => $count > 0 ? round($avgHalfStars / 2, 2) : null,
        ])->saveQuietly();
    }
}
