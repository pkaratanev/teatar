<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\DiaryEntry;
use App\Models\Production;
use App\Services\TheatreLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The signed-in user's own record of a production: rating, review, visits,
 * watchlist, and a one-tap "log" that does several at once.
 */
class ActivityController extends Controller
{
    public function __construct(private readonly TheatreLog $log) {}

    public function rate(Request $request, Production $production): JsonResponse
    {
        $data = $request->validate(['half_stars' => ['required', 'integer', 'between:1,10']]);
        $this->log->rate($request->user(), $production, $data['half_stars']);

        return $this->state($request, $production);
    }

    public function unrate(Request $request, Production $production): JsonResponse
    {
        $this->log->unrate($request->user(), $production);

        return $this->state($request, $production);
    }

    public function review(Request $request, Production $production): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'has_spoilers' => ['sometimes', 'boolean'],
        ]);
        $this->log->review($request->user(), $production, $data['body'], (bool) ($data['has_spoilers'] ?? false));

        return $this->state($request, $production);
    }

    public function unreview(Request $request, Production $production): JsonResponse
    {
        $this->log->unreview($request->user(), $production);

        return $this->state($request, $production);
    }

    public function logVisit(Request $request, Production $production): JsonResponse
    {
        $data = $request->validate(self::visitRules($production));
        $this->log->logVisit($request->user(), $production, self::seenOn($data), $data['performance_id'] ?? null);

        return $this->state($request, $production, 201);
    }

    public function deleteVisit(Request $request, DiaryEntry $entry): Response
    {
        abort_unless($entry->user_id === $request->user()->id, 404);
        $entry->delete();

        return response()->noContent();
    }

    public function watch(Request $request, Production $production): JsonResponse
    {
        $this->log->watch($request->user(), $production);

        return $this->state($request, $production);
    }

    public function unwatch(Request $request, Production $production): JsonResponse
    {
        $this->log->unwatch($request->user(), $production);

        return $this->state($request, $production);
    }

    /**
     * One tap after the show: "seen on", optionally a rating and a review.
     */
    public function log(Request $request, Production $production): JsonResponse
    {
        $data = $request->validate([
            ...self::visitRules($production),
            'half_stars' => ['nullable', 'integer', 'between:1,10'],
            'review' => ['nullable', 'string', 'min:2', 'max:5000'],
            'has_spoilers' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $this->log->logVisit($user, $production, self::seenOn($data), $data['performance_id'] ?? null);
        if (isset($data['half_stars'])) {
            $this->log->rate($user, $production, $data['half_stars']);
        }
        if (isset($data['review']) && trim($data['review']) !== '') {
            $this->log->review($user, $production, $data['review'], (bool) ($data['has_spoilers'] ?? false));
        }

        return $this->state($request, $production, 201);
    }

    /** @return array<string, list<mixed>> */
    private static function visitRules(Production $production): array
    {
        $today = now(config('app.theatre_timezone'))->toDateString();

        return [
            'seen_on' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:1950-01-01', "before_or_equal:{$today}"],
            'performance_id' => ['nullable', 'integer', "exists:performances,id,production_id,{$production->id}"],
        ];
    }

    /** @param  array<string, mixed>  $data */
    private static function seenOn(array $data): CarbonImmutable
    {
        $tz = config('app.theatre_timezone');

        return isset($data['seen_on'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $data['seen_on'], $tz)
            : CarbonImmutable::now($tz);
    }

    private function state(Request $request, Production $production, int $status = 200): JsonResponse
    {
        $state = $this->log->stateFor($request->user(), $production);
        $state['review'] = $state['review'] ? new ReviewResource($state['review']) : null;

        return response()->json(['data' => $state], $status);
    }
}
