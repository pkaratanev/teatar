<?php

namespace App\Http\Resources;

use App\Models\Credit;
use App\Models\Production;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Production */
class ProductionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...(new ProductionSummaryResource($this->resource))->toArray($request),
            'synopsis' => $this->synopsis,
            'age_advisory' => $this->age_advisory,
            'running_time_minutes' => $this->running_time_minutes,
            'premiered_on' => $this->premiered_on?->toDateString(),
            'box_office_url' => $this->theatre->box_office_url,
            'credits' => $this->credits->map(fn (Credit $credit) => [
                'person' => ['id' => $credit->person->id, 'slug' => $credit->person->slug, 'name' => $credit->person->name],
                'role_type' => $credit->role_type,
                'role_label' => $credit->role_type->getLabel(),
                'character_name' => $credit->character_name,
            ]),
            'upcoming_performances' => PerformanceResource::collection($this->whenLoaded('performances')),
        ];
    }
}
