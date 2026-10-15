<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $production_id
 * @property \Illuminate\Support\Carbon $starts_at
 * @property string|null $price_from
 * @property string|null $price_to
 * @property bool $is_cancelled
 * @property string $source
 * @property-read Production $production
 * @property-read Stage|null $stage
 */
#[Fillable(['production_id', 'stage_id', 'starts_at', 'ticket_url', 'price_from', 'price_to', 'is_cancelled', 'source', 'source_url'])]
class Performance extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'price_from' => 'decimal:2',
            'price_to' => 'decimal:2',
            'is_cancelled' => 'boolean',
        ];
    }

    /** @return BelongsTo<Production, $this> */
    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    /** @return BelongsTo<Stage, $this> */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    /** @param Builder<self> $query */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now())->where('is_cancelled', false);
    }
}
