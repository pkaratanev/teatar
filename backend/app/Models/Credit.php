<?php

namespace App\Models;

use App\Enums\CreditRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['production_id', 'person_id', 'role_type', 'character_name', 'sort'])]
class Credit extends Model
{
    protected function casts(): array
    {
        return ['role_type' => CreditRole::class];
    }

    /** @return BelongsTo<Production, $this> */
    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
