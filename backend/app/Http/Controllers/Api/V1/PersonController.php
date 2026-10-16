<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PersonResource;
use App\Models\Person;
use App\Services\Social;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    public function show(Request $request, Person $person, Social $social): PersonResource
    {
        $person->load(['credits.production.theatre', 'credits.production.media']);

        return (new PersonResource($person))->additional(['social' => [
            'followers_count' => $social->followersCount($person),
            'is_following' => $social->isFollowing($request->user('sanctum'), $person),
        ]]);
    }
}
