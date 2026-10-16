<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DiaryEntryResource;
use App\Http\Resources\ProductionSummaryResource;
use App\Http\Resources\ReviewResource;
use App\Models\DiaryEntry;
use App\Models\Production;
use App\Models\Rating;
use App\Models\Review;
use App\Models\User;
use App\Services\ProfileStats;
use App\Services\Social;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileController extends Controller
{
    /** A public profile: who, stats, latest diary and reviews. */
    public function show(Request $request, string $username, ProfileStats $stats, Social $social): JsonResponse
    {
        $user = User::where('username', $username)->firstOrFail();
        $viewer = $request->user('sanctum');

        return response()->json(['data' => [
            'username' => $user->username,
            'name' => $user->name,
            'bio' => $user->bio,
            'joined_at' => $user->created_at?->toDateString(),
            'followers_count' => $social->followersCount($user),
            'following_count' => $social->followingCount($user),
            'is_following' => $social->isFollowing($viewer, $user),
            'is_me' => $viewer?->is($user) ?? false,
            'stats' => $stats->for($user),
            'recent_diary' => DiaryEntryResource::collection($this->diaryQuery($user)->limit(10)->get()),
            'recent_reviews' => ReviewResource::collection(
                Review::where('user_id', $user->id)->visibleTo($viewer)->with('production.theatre', 'production.media')->latest()->limit(5)->get()
            ),
        ]]);
    }

    public function diary(Request $request): AnonymousResourceCollection
    {
        return DiaryEntryResource::collection($this->diaryQuery($request->user())->paginate(30));
    }

    public function watchlist(Request $request): AnonymousResourceCollection
    {
        $productions = Production::query()
            ->join('watchlist_items', 'watchlist_items.production_id', '=', 'productions.id')
            ->where('watchlist_items.user_id', $request->user()->id)
            ->orderByDesc('watchlist_items.created_at')
            ->select('productions.*')
            ->with(['theatre', 'media'])
            ->withMin(['performances as next_performance_at' => fn ($q) => $q->where('starts_at', '>=', now())->where('is_cancelled', false)], 'starts_at')
            ->paginate(30);

        return ProductionSummaryResource::collection($productions);
    }

    public function ratings(Request $request): AnonymousResourceCollection
    {
        $productions = Production::query()
            ->join('ratings', 'ratings.production_id', '=', 'productions.id')
            ->where('ratings.user_id', $request->user()->id)
            ->orderByDesc('ratings.updated_at')
            ->select('productions.*', 'ratings.half_stars as my_half_stars')
            ->with(['theatre', 'media'])
            ->paginate(30);

        return ProductionSummaryResource::collection($productions);
    }

    /** @return \Illuminate\Database\Eloquent\Builder<DiaryEntry> */
    private function diaryQuery(User $user)
    {
        return DiaryEntry::query()
            ->where('user_id', $user->id)
            ->with(['production.theatre', 'production.media'])
            ->addSelect(['half_stars' => Rating::select('half_stars')
                ->whereColumn('ratings.user_id', 'diary_entries.user_id')
                ->whereColumn('ratings.production_id', 'diary_entries.production_id')
                ->limit(1)])
            ->orderByDesc('seen_on')
            ->orderByDesc('id');
    }
}
