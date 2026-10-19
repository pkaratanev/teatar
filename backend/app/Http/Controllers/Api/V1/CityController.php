<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Theatre;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class CityController extends Controller
{
    /**
     * Cities with at least one active theatre, for the app's city switcher.
     * Biggest upcoming programme first, so София leads.
     */
    public function index(): JsonResponse
    {
        $cities = Theatre::query()
            ->where('is_active', true)
            ->withCount(['performances as upcoming_count' => fn ($q) => $q
                ->where('performances.starts_at', '>=', now())
                ->where('performances.is_cancelled', false)])
            ->get(['id', 'city'])
            ->groupBy('city')
            ->map(fn (Collection $theatres, string $city) => [
                'name' => $city,
                'theatres_count' => $theatres->count(),
                'upcoming_count' => (int) $theatres->sum('upcoming_count'),
            ])
            ->sortByDesc('upcoming_count')
            ->values();

        return response()->json(['data' => $cities]);
    }
}
