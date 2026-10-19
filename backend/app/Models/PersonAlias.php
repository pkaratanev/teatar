<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Another spelling of a person's name, kept from a merge (see PersonMerge). */
#[Fillable(['person_id', 'name', 'name_key'])]
class PersonAlias extends Model
{
    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
