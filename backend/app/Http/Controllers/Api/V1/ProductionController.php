<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductionResource;
use App\Models\Production;

class ProductionController extends Controller
{
    public function show(Production $production): ProductionResource
    {
        $production->load([
            'theatre',
            'media',
            'credits.person',
            'performances' => fn ($q) => $q->upcoming()->with('stage')->limit(30),
        ]);

        return new ProductionResource($production);
    }
}
