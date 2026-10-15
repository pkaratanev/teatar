<?php

namespace App\Http\Resources;

use App\Models\Performance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Performance */
class PerformanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'starts_at' => $this->starts_at->toIso8601String(),
            'stage' => $this->stage?->name,
            'ticket_url' => $this->ticket_url,
            'price_from' => $this->price_from !== null ? (float) $this->price_from : null,
            'price_to' => $this->price_to !== null ? (float) $this->price_to : null,
            'production' => new ProductionSummaryResource($this->whenLoaded('production')),
        ];
    }
}
