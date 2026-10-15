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
 * @property-read Theatre $theatre
 */
#[Fillable(['theatre_id', 'title', 'title_en', 'slug', 'author', 'is_guest', 'visiting_company', 'synopsis', 'age_advisory', 'running_time_minutes', 'premiered_on', 'status', 'source', 'source_url'])]
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
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('poster')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(400)->height(600);
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
