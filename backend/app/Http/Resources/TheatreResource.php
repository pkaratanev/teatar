<?php

namespace App\Http\Resources;

use App\Models\Theatre;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Theatre */
class TheatreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'short_name' => $this->short_name ?? $this->name,
            'city' => $this->city,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'phone' => $this->phone,
            'website' => $this->website,
            'box_office_url' => $this->box_office_url,
            'logo_url' => $this->getFirstMediaUrl('logo') ?: null,
            'upcoming_count' => $this->when(array_key_exists('upcoming_count', $this->resource->getAttributes()), fn () => (int) $this->resource->getAttribute('upcoming_count')),
            'productions' => ProductionSummaryResource::collection($this->whenLoaded('productions')),
        ];
    }
}
