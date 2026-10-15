<?php

namespace App\Http\Resources;

use App\Models\Production;
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
            'status' => $this->status,
            'poster_url' => $this->getFirstMediaUrl('poster', 'card') ?: null,
            'rating_average' => $this->rating_average,
            'ratings_count' => $this->ratings_count,
            'theatre' => $this->whenLoaded('theatre', fn () => [
                'id' => $this->theatre->id,
                'name' => $this->theatre->name,
            ]),
        ];
    }
}
