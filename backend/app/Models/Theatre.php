<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property string $slug
 * @property string|null $website
 * @property string|null $adapter
 */
#[Fillable(['name', 'slug', 'city', 'address', 'lat', 'lng', 'website', 'box_office_url', 'phone', 'adapter', 'is_active', 'source', 'source_url'])]
class Theatre extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    /** @return HasMany<Stage, $this> */
    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class);
    }

    /** @return HasMany<Production, $this> */
    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    /** @return HasManyThrough<Performance, Production, $this> */
    public function performances(): HasManyThrough
    {
        return $this->hasManyThrough(Performance::class, Production::class);
    }

    /** @return HasMany<AdapterRun, $this> */
    public function adapterRuns(): HasMany
    {
        return $this->hasMany(AdapterRun::class);
    }
}
