<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PersonResource;
use App\Models\Person;

class PersonController extends Controller
{
    public function show(Person $person): PersonResource
    {
        $person->load(['credits.production.theatre', 'credits.production.media']);

        return new PersonResource($person);
    }
}
