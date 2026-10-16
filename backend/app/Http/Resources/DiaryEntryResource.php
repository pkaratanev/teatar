<?php

namespace App\Http\Resources;

use App\Models\DiaryEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DiaryEntry */
class DiaryEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seen_on' => $this->seen_on->toDateString(),
            'performance_id' => $this->performance_id,
            'half_stars' => $this->resource->getAttribute('half_stars'),
            'production' => new ProductionSummaryResource($this->whenLoaded('production')),
        ];
    }
}
