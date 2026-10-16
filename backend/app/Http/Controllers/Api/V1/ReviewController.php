<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Production;
use App\Models\Rating;
use App\Models\Report;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ReviewController extends Controller
{
    /** Public review feed for a production, newest first; shadow-banned authors only see their own. */
    public function index(Request $request, Production $production): AnonymousResourceCollection
    {
        $reviews = Review::query()
            ->where('production_id', $production->id)
            ->visibleTo($request->user('sanctum'))
            ->with('user')
            ->addSelect(['author_half_stars' => Rating::select('half_stars')
                ->whereColumn('ratings.user_id', 'reviews.user_id')
                ->whereColumn('ratings.production_id', 'reviews.production_id')
                ->limit(1)])
            ->when($request->user('sanctum'), fn ($q, $viewer) => $q->withExists(['likes as liked_by_me' => fn ($l) => $l->where('user_id', $viewer->id)]))
            ->latest()
            ->paginate(20);

        return ReviewResource::collection($reviews);
    }

    public function report(Request $request, Review $review): Response
    {
        $data = $request->validate([
            'reason' => ['required', 'in:spam,abuse,spoiler,other'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_if($review->user_id === $request->user()->id, 422, 'Не можете да докладвате собственото си ревю.');

        Report::updateOrCreate(
            ['reporter_id' => $request->user()->id, 'review_id' => $review->id],
            ['reason' => $data['reason'], 'note' => $data['note'] ?? null, 'resolved_at' => null, 'resolved_by' => null],
        );

        return response()->noContent();
    }
}
