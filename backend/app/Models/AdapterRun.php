<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['theatre_id', 'adapter', 'status', 'performances_found', 'performances_upserted', 'productions_created', 'error', 'started_at', 'finished_at'])]
class AdapterRun extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Theatre, $this> */
    public function theatre(): BelongsTo
    {
        return $this->belongsTo(Theatre::class);
    }
}
