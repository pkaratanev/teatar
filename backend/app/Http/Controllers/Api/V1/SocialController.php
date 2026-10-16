<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Follow;
use App\Models\Person;
use App\Models\Rating;
use App\Models\Review;
use App\Models\User;
use App\Services\Social;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SocialController extends Controller
{
    public function __construct(private readonly Social $social) {}

    public function followUser(Request $request, string $username): JsonResponse
    {
        $target = User::where('username', $username)->firstOrFail();
        abort_if($target->is($request->user()), 422, 'Не можете да следвате себе си.');
        $this->social->follow($request->user(), $target);

        return $this->followState($request, $target);
    }

    public function unfollowUser(Request $request, string $username): JsonResponse
    {
        $target = User::where('username', $username)->firstOrFail();
        $this->social->unfollow($request->user(), $target);

        return $this->followState($request, $target);
    }

    public function followPerson(Request $request, Person $person): JsonResponse
    {
        $this->social->follow($request->user(), $person);

        return $this->followState($request, $person);
    }

    public function unfollowPerson(Request $request, Person $person): JsonResponse
    {
        $this->social->unfollow($request->user(), $person);

        return $this->followState($request, $person);
    }

    public function like(Request $request, Review $review): JsonResponse
    {
        abort_if($review->user_id === $request->user()->id, 422, 'Не можете да харесате собственото си ревю.');
        $this->social->like($request->user(), $review, true);

        return response()->json(['data' => ['likes_count' => $review->likes_count, 'liked_by_me' => true]]);
    }

    public function unlike(Request $request, Review $review): JsonResponse
    {
        $this->social->like($request->user(), $review, false);

        return response()->json(['data' => ['likes_count' => $review->likes_count, 'liked_by_me' => false]]);
    }

    /** Reviews by the people you follow, newest first. */
    public function feed(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $followed = Follow::where('user_id', $user->id)
            ->where('followable_type', (new User)->getMorphClass())
            ->select('followable_id');

        $reviews = Review::query()
            ->whereIn('user_id', $followed)
            ->visibleTo($user)
            ->with(['user', 'production.theatre', 'production.media'])
            ->addSelect(['author_half_stars' => Rating::select('half_stars')
                ->whereColumn('ratings.user_id', 'reviews.user_id')
                ->whereColumn('ratings.production_id', 'reviews.production_id')
                ->limit(1)])
            ->withExists(['likes as liked_by_me' => fn (Builder $q) => $q->where('user_id', $user->id)])
            ->latest()
            ->paginate(20);

        return ReviewResource::collection($reviews);
    }

    /** Who you follow: users and people. */
    public function following(Request $request): JsonResponse
    {
        $follows = Follow::where('user_id', $request->user()->id)->with('followable')->latest()->get();

        return response()->json(['data' => [
            'users' => $follows->filter(fn (Follow $f) => $f->followable instanceof User)
                ->map(fn (Follow $f) => ['username' => $f->followable->username, 'name' => $f->followable->name])->values(),
            'people' => $follows->filter(fn (Follow $f) => $f->followable instanceof Person)
                ->map(fn (Follow $f) => ['id' => $f->followable->id, 'name' => $f->followable->name])->values(),
        ]]);
    }

    private function followState(Request $request, User|Person $target): JsonResponse
    {
        return response()->json(['data' => [
            'is_following' => $this->social->isFollowing($request->user(), $target),
            'followers_count' => $this->social->followersCount($target),
        ]]);
    }
}
