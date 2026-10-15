<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TheatreResource;
use App\Models\Theatre;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TheatreController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $city = $request->string('city', 'София')->toString();

        return TheatreResource::collection(
            Theatre::where('is_active', true)->where('city', $city)->orderBy('name')->get()
        );
    }

    public function show(Theatre $theatre): TheatreResource
    {
        $theatre->load(['productions' => fn ($q) => $q->running()->with('media')->orderBy('title')]);

        return new TheatreResource($theatre);
    }
}
