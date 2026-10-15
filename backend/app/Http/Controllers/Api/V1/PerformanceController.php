<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PerformanceResource;
use App\Models\Performance;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PerformanceController extends Controller
{
    /**
     * The programme: upcoming performances in a city, in date order.
     *
     * `range` is a shortcut for the app's three tabs; `from` / `to` (Y-m-d) override it.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'range' => ['nullable', 'in:tonight,week,month'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'city' => ['nullable', 'string', 'max:64'],
            'theatre_id' => ['nullable', 'integer', 'exists:theatres,id'],
        ]);

        $tz = config('app.theatre_timezone');
        $today = CarbonImmutable::now($tz)->startOfDay();

        [$from, $to] = match ($data['range'] ?? 'week') {
            'tonight' => [CarbonImmutable::now($tz), $today->endOfDay()],
            'week' => [CarbonImmutable::now($tz), $today->addDays(7)->endOfDay()],
            'month' => [CarbonImmutable::now($tz), $today->addMonth()->endOfDay()],
        };

        if (isset($data['from'])) {
            $from = CarbonImmutable::createFromFormat('Y-m-d', $data['from'], $tz)->startOfDay();
        }
        if (isset($data['to'])) {
            $to = CarbonImmutable::createFromFormat('Y-m-d', $data['to'], $tz)->endOfDay();
        }

        $performances = Performance::query()
            ->with(['production.theatre', 'production.media', 'stage'])
            ->where('is_cancelled', false)
            ->whereBetween('starts_at', [$from->utc(), $to->utc()])
            ->whereHas('production.theatre', function (Builder $q) use ($data) {
                $q->where('is_active', true)->where('city', $data['city'] ?? 'София');
                if (isset($data['theatre_id'])) {
                    $q->whereKey($data['theatre_id']);
                }
            })
            ->orderBy('starts_at')
            ->paginate(50);

        return PerformanceResource::collection($performances);
    }
}
