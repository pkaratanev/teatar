<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['theatre_id', 'name', 'capacity'])]
class Stage extends Model
{
    /** @return BelongsTo<Theatre, $this> */
    public function theatre(): BelongsTo
    {
        return $this->belongsTo(Theatre::class);
    }
}
