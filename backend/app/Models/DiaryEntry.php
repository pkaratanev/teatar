<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $performance_id
 * @property \Illuminate\Support\Carbon $seen_on
 */
#[Fillable(['user_id', 'production_id', 'performance_id', 'seen_on'])]
class DiaryEntry extends Model
{
    protected function casts(): array
    {
        return ['seen_on' => 'date'];
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

    /** @return BelongsTo<Performance, $this> */
    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }
}
