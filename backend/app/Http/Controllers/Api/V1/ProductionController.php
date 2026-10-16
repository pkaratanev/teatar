<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductionResource;
use App\Http\Resources\ReviewResource;
use App\Models\Production;
use App\Services\TheatreLog;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function show(Request $request, Production $production, TheatreLog $log): ProductionResource
    {
        $production->load([
            'theatre',
            'media',
            'credits.person',
            'performances' => fn ($q) => $q->upcoming()->with('stage')->limit(30),
        ]);

        $resource = new ProductionResource($production);

        // Signed-in users also get their own rating, review, visits and watchlist state.
        if ($user = $request->user('sanctum')) {
            $me = $log->stateFor($user, $production);
            $me['review'] = $me['review'] ? new ReviewResource($me['review']) : null;
            $resource->additional(['me' => $me]);
        }

        return $resource;
    }
}
