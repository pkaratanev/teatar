<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductionSummaryResource;
use App\Models\Person;
use App\Models\Production;
use App\Models\Theatre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /** Search titles, people and theatres at once; typo-tolerant on Postgres via pg_trgm. */
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->validate(['q' => ['required', 'string', 'min:2', 'max:100']])['q']);

        $productions = $this->match(Production::query()->with(['theatre', 'media']), 'title', $q)->limit(10)->get();
        $people = $this->match(Person::query(), 'name', $q)->limit(10)->get(['id', 'slug', 'name']);
        $theatres = $this->match(Theatre::query()->where('is_active', true), 'name', $q)->limit(5)->get(['id', 'slug', 'name']);

        return response()->json([
            'productions' => ProductionSummaryResource::collection($productions),
            'people' => $people,
            'theatres' => $theatres,
        ]);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function match(Builder $query, string $column, string $q): Builder
    {
        if (DB::getDriverName() === 'pgsql') {
            return $query
                ->where(fn (Builder $w) => $w->where($column, 'ilike', "%{$q}%")->orWhereRaw("{$column} % ?", [$q]))
                ->orderByRaw("similarity({$column}, ?) DESC", [$q]);
        }

        return $query->where($column, 'like', "%{$q}%")->orderBy($column);
    }
}
