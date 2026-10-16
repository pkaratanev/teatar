<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Review */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'has_spoilers' => $this->has_spoilers,
            'likes_count' => $this->likes_count,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            // The author's rating of the same production, when the query added it.
            'half_stars' => $this->when(array_key_exists('author_half_stars', $this->resource->getAttributes()), fn () => $this->resource->getAttribute('author_half_stars')),
            'user' => $this->whenLoaded('user', fn () => ['username' => $this->user->username, 'name' => $this->user->name]),
            'production' => new ProductionSummaryResource($this->whenLoaded('production')),
            'is_mine' => $request->user('sanctum')?->id === $this->user_id,
        ];
    }
}
