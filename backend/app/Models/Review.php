<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property ReviewStatus $status
 * @property bool $has_spoilers
 * @property int $user_id
 * @property string $body
 * @property int $likes_count
 * @property int|null $open_reports_count
 */
#[Fillable(['user_id', 'production_id', 'body', 'has_spoilers', 'status'])]
class Review extends Model
{
    protected function casts(): array
    {
        return [
            'has_spoilers' => 'boolean',
            'status' => ReviewStatus::class,
        ];
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

    /** @return HasMany<ReviewLike, $this> */
    public function likes(): HasMany
    {
        return $this->hasMany(ReviewLike::class);
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /** @return HasMany<Report, $this> */
    public function openReports(): HasMany
    {
        return $this->hasMany(Report::class)->whereNull('resolved_at');
    }

    /**
     * Reviews a given viewer may see: visible ones by non-shadow-banned users,
     * plus the viewer's own (a shadow-banned user still sees their reviews).
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, ?User $viewer): void
    {
        $query->where('status', ReviewStatus::Visible)
            ->where(function (Builder $q) use ($viewer) {
                $q->whereHas('user', fn (Builder $u) => $u->whereNull('shadow_banned_at'));
                if ($viewer) {
                    $q->orWhere('user_id', $viewer->id);
                }
            });
    }
}
