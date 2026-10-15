<?php

namespace App\Http\Resources;

use App\Enums\ProductionStatus;
use App\Models\Credit;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Person */
class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $credits = $this->credits->map(fn (Credit $credit) => [
            'production' => (new ProductionSummaryResource($credit->production))->toArray($request),
            'role_type' => $credit->role_type,
            'role_label' => $credit->role_type->getLabel(),
            'character_name' => $credit->character_name,
        ]);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'bio' => $this->bio,
            'born_year' => $this->born_year,
            'headshot_url' => $this->getFirstMediaUrl('headshot') ?: null,
            'current' => $credits->filter(fn (array $c) => $c['production']['status'] === ProductionStatus::Running)->values(),
            'past' => $credits->filter(fn (array $c) => $c['production']['status'] === ProductionStatus::Closed)->values(),
        ];
    }
}
