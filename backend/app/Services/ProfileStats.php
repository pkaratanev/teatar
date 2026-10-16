<?php

namespace App\Services;

use App\Enums\CreditRole;
use App\Models\DiaryEntry;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Numbers for the profile screen: this year, histogram, favourite theatre, most-seen actor. */
class ProfileStats
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $user): array
    {
        $year = now(config('app.theatre_timezone'))->year;

        $histogram = array_fill(1, 10, 0);
        foreach (Rating::where('user_id', $user->id)->selectRaw('half_stars, count(*) as n')->groupBy('half_stars')->get() as $row) {
            $histogram[(int) $row->getAttribute('half_stars')] = (int) $row->getAttribute('n');
        }

        $theatre = DB::table('diary_entries')
            ->join('productions', 'productions.id', '=', 'diary_entries.production_id')
            ->join('theatres', 'theatres.id', '=', 'productions.theatre_id')
            ->where('diary_entries.user_id', $user->id)
            ->groupBy('theatres.id', 'theatres.name')
            ->orderByDesc(DB::raw('count(*)'))->orderBy('theatres.name')
            ->first(['theatres.id', 'theatres.name', DB::raw('count(*) as visits')]);

        $actor = DB::table('diary_entries')
            ->join('credits', 'credits.production_id', '=', 'diary_entries.production_id')
            ->join('people', 'people.id', '=', 'credits.person_id')
            ->where('diary_entries.user_id', $user->id)
            ->where('credits.role_type', CreditRole::Actor->value)
            ->groupBy('people.id', 'people.name')
            ->orderByDesc(DB::raw('count(*)'))->orderBy('people.name')
            ->first(['people.id', 'people.name', DB::raw('count(*) as times')]);

        return [
            'seen_total' => DiaryEntry::where('user_id', $user->id)->count(),
            'seen_this_year' => DiaryEntry::where('user_id', $user->id)->whereYear('seen_on', $year)->count(),
            'ratings_count' => array_sum($histogram),
            // Ten counts: [0] = ½★, [1] = ★ … [9] = ★★★★★.
            'ratings_histogram' => array_values($histogram),
            'favourite_theatre' => $theatre ? ['id' => (int) $theatre->id, 'name' => $theatre->name, 'visits' => (int) $theatre->visits] : null,
            'most_seen_actor' => $actor ? ['id' => (int) $actor->id, 'name' => $actor->name, 'times' => (int) $actor->times] : null,
        ];
    }
}
