<?php

namespace App\Http\Resources;

use App\Models\Production;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The compact production card used in lists.
 *
 * @mixin Production
 */
class ProductionSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'author' => $this->author,
            'is_guest' => $this->is_guest,
            'visiting_company' => $this->visiting_company,
            'status' => $this->status,
            'poster_url' => $this->posterUrl(),
            'rating_average' => $this->rating_average,
            'ratings_count' => $this->ratings_count,
            // Present only on lists that add them (watchlist, my ratings).
            'next_performance_at' => $this->when(array_key_exists('next_performance_at', $this->resource->getAttributes()),
                fn () => $this->resource->getAttribute('next_performance_at') ? CarbonImmutable::parse($this->resource->getAttribute('next_performance_at'))->toIso8601String() : null),
            'my_half_stars' => $this->when(array_key_exists('my_half_stars', $this->resource->getAttributes()), fn () => $this->resource->getAttribute('my_half_stars')),
            'theatre' => $this->whenLoaded('theatre', fn () => [
                'id' => $this->theatre->id,
                'name' => $this->theatre->name,
            ]),
        ];
    }
}
