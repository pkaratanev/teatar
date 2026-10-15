<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['name', 'slug', 'bio', 'born_year', 'source', 'source_url'])]
class Person extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'people';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('headshot')->singleFile();
    }

    /** @return HasMany<Credit, $this> */
    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    /** @return BelongsToMany<Production, $this> */
    public function productions(): BelongsToMany
    {
        return $this->belongsToMany(Production::class, 'credits')->withPivot(['role_type', 'character_name']);
    }

    /** @return MorphMany<Follow, $this> */
    public function followers(): MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }
}
