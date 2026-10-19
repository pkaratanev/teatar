<?php

namespace App\Services;

use App\Models\Person;
use Illuminate\Support\Collection;

/**
 * People who are probably the same person, grouped. Two names match when the first three letters
 * of the first name and the surname (without a nickname or character after a dash) are the same,
 * ignoring case, spaces, dashes and middle names:
 *
 *   „Христо Станчев“ ~ „Христо Ботев Станчев“, „Любо Нейков“ ~ „Любомир Нейков“,
 *   „Александър Карасански-Джош“ ~ „Александър Карасански“, „Коцев – Шошо“ ~ „Коцев-Шошо“.
 *
 * It only suggests: real namesakes (two „Стоян Радев“) also match, so an admin decides.
 */
class PeopleDuplicates
{
    /** @return Collection<string, Collection<int, Person>> group key => people, only groups of 2 or more */
    public function groups(): Collection
    {
        return Person::query()
            ->get(['id', 'name'])
            ->groupBy(fn (Person $person) => self::key($person->name))
            ->filter(fn (Collection $people, string $key) => $key !== '' && $people->count() > 1)
            ->sortKeys();
    }

    /** @return list<int> ids of people in a group, in group order (for sorting the list) */
    public function ids(): array
    {
        return $this->groups()->flatten()->pluck('id')->all();
    }

    public static function key(string $name): string
    {
        $words = preg_split('/\s+/u', mb_strtolower(People::clean($name))) ?: [];
        // A spaced dash starts a nickname or character: "коцев - шошо" → stop at "коцев".
        $dash = array_search('-', $words, true);
        if ($dash !== false) {
            $words = array_slice($words, 0, $dash);
        }
        // Trailing initials ("Стоян Радев Ге. К.") are not the surname.
        while (count($words) > 2 && preg_match('/\.|^\p{L}{1,2}$/u', (string) end($words))) {
            array_pop($words);
        }
        if (count($words) < 2) {
            return '';
        }

        $first = mb_substr(preg_replace('/[^\p{L}]/u', '', $words[0]) ?? '', 0, 3);
        $last = preg_replace('/[^\p{L}]/u', '', explode('-', (string) end($words))[0]) ?? '';

        return $first !== '' && $last !== '' ? "{$last} {$first}" : '';
    }
}
