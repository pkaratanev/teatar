<?php

namespace App\Services;

use App\Models\Follow;
use App\Models\Person;
use App\Models\PersonAlias;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Merges a duplicate person into the one that stays: credits and followers move over
 * (without doubling a credit the kept person already has), empty fields are filled
 * from the duplicate, its name is kept as an alias (so imports don't create it again),
 * and the duplicate is deleted.
 */
class PersonMerge
{
    /** @return array{credits: int, followers: int} what moved over */
    public function merge(Person $keep, Person $duplicate): array
    {
        if ($keep->is($duplicate)) {
            throw new InvalidArgumentException('A person cannot be merged into themselves.');
        }

        return DB::transaction(function () use ($keep, $duplicate) {
            $moved = ['credits' => 0, 'followers' => 0];

            foreach ($duplicate->credits()->get() as $credit) {
                $exists = $keep->credits()
                    ->where('production_id', $credit->production_id)
                    ->where('role_type', $credit->role_type)
                    ->where(fn ($q) => $credit->character_name === null
                        ? $q->whereNull('character_name')
                        : $q->where('character_name', $credit->character_name))
                    ->exists();

                if ($exists) {
                    $credit->delete();
                } else {
                    $credit->update(['person_id' => $keep->id]);
                    $moved['credits']++;
                }
            }

            foreach ($duplicate->followers()->get() as $follow) {
                /** @var Follow $follow */
                if ($keep->followers()->where('user_id', $follow->user_id)->exists()) {
                    $follow->delete();
                } else {
                    $follow->update(['followable_id' => $keep->id]);
                    $moved['followers']++;
                }
            }

            foreach (['bio', 'born_year', 'source_url'] as $field) {
                if (blank($keep->{$field}) && filled($duplicate->{$field})) {
                    $keep->{$field} = $duplicate->{$field};
                }
            }
            $keep->save();

            $headshot = $duplicate->getFirstMedia('headshot');
            if ($headshot !== null && $keep->getFirstMedia('headshot') === null) {
                $headshot->move($keep, 'headshot');
            }

            // Remember the duplicate's spelling (and the ones it already had), so imports find the kept person.
            $duplicate->aliases()->update(['person_id' => $keep->id]);
            if (People::key($duplicate->name) !== People::key($keep->name)) {
                PersonAlias::updateOrCreate(
                    ['name_key' => People::key($duplicate->name)],
                    ['person_id' => $keep->id, 'name' => $duplicate->name],
                );
            }
            $keep->aliases()->where('name_key', People::key($keep->name))->delete();

            $duplicate->delete();

            return $moved;
        });
    }
}
