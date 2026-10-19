<?php

namespace App\Services;

use App\Enums\CatalogueSource;
use App\Enums\CreditRole;
use App\Models\Person;
use App\Models\Production;

/**
 * Credits from label/value pairs, as theatres list them ("Режисьор" → "Иван Петров"), shared
 * by the cast reader (pairs read from a theatre's page) and the paste box in the admin (text):
 *
 *   Режисьор: Иван Петров
 *   Сценография и костюми: Мария Иванова
 *   Актьори: Анна Колева, Петър Стоянов (Хамлет)
 *   Актьори:
 *   Анна Колева - Офелия
 *
 * Adding is safe to repeat: an existing credit is never doubled and nothing is removed.
 *
 * A name may end in "#123" (that exact person, for two people with the same name) or "#нов" (a new
 * person even though the name exists).
 *
 * @phpstan-type ParsedCredit array{role: CreditRole, name: string, character: ?string, pick?: int|'new'|null}
 * @phpstan-type Parsed array{credits: list<ParsedCredit>, author: ?string, skipped: list<string>}
 */
class Credits
{
    /** Label pattern (lower case) → roles; the first match wins, so combined labels come first. */
    private const LABELS = [
        '/сценограф\w*\s+и\s+костюм|художник/u' => [CreditRole::SetDesigner, CreditRole::CostumeDesigner],
        '/режис|^постановка\b/u' => [CreditRole::Director],
        '/^автор|^по пиесата|^пиеса\b|^текст\b/u' => [CreditRole::Playwright],
        '/драматург/u' => [CreditRole::Other],
        '/превод/u' => [CreditRole::Translator],
        '/сценограф/u' => [CreditRole::SetDesigner],
        '/костюм/u' => [CreditRole::CostumeDesigner],
        '/музик|композитор/u' => [CreditRole::Composer],
        '/хореограф/u' => [CreditRole::Choreographer],
        '/светлин|осветлен|^светли/u' => [CreditRole::LightingDesigner],
        '/^участват|^актьор|^в ролите|^играят|^роли\b|изпълнители|^актьорски/u' => [CreditRole::Actor],
    ];

    public function __construct(private People $people) {}

    /** The roles a label stands for, or null when it isn't a credit we keep (Фотограф, Продукция …). */
    public static function roles(string $label): ?array
    {
        $label = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $label) ?? $label, " \t\n\r\0\x0B:;"));
        foreach (self::LABELS as $pattern => $roles) {
            if (preg_match($pattern, $label)) {
                return $roles;
            }
        }

        return null;
    }

    /**
     * Text from the paste box: a known label starts a group; names follow on the same line
     * (comma separated) or on the next lines. Characters in brackets or after " - ".
     *
     * @return Parsed
     */
    public function parse(string $text): array
    {
        $pairs = [];
        $skipped = [];
        $label = null;

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^([^:]{2,40}):\s*(.*)$/u', $line, $m)) {
                $label = self::roles($m[1]) === null ? null : $m[1];
                if ($label === null) {
                    $skipped[] = $line;
                } elseif (trim($m[2]) !== '') {
                    $pairs[] = [$label, $m[2]];
                }

                continue;
            }
            if ($label === null) {
                $skipped[] = $line;

                continue;
            }
            $pairs[] = [$label, $line];
        }

        $parsed = $this->fromPairs($pairs, characters: true);
        $parsed['skipped'] = $skipped;

        return $parsed;
    }

    /**
     * Pairs read from a theatre's page. Unknown labels are left out. Without $characters, a
     * " - " between names is part of a double surname ("Аврамова - Бочева"), not a character.
     *
     * @param  list<array{0: string, 1: string}>  $pairs
     * @return Parsed
     */
    public function fromPairs(array $pairs, bool $characters = false): array
    {
        $credits = [];
        $author = null;
        $skipped = [];

        foreach ($pairs as [$label, $value]) {
            $roles = self::roles($label);
            if ($roles === null) {
                $skipped[] = trim($label);

                continue;
            }

            if ($roles === [CreditRole::Playwright] && $author === null) {
                $author = People::clean(preg_replace('/^от\s+/u', '', People::clean($value)) ?? $value) ?: null;
            }

            foreach (self::names($value, $characters) as [$name, $character, $pick]) {
                foreach ($roles as $role) {
                    $credits[] = ['role' => $role, 'name' => $name, 'character' => $role === CreditRole::Actor ? $character : null, 'pick' => $pick];
                }
            }
        }

        return ['credits' => $credits, 'author' => $author, 'skipped' => $skipped];
    }

    /**
     * Adds the credits to the production, and the author text when the production has none.
     *
     * @param  Parsed  $parsed
     * @return int how many credits were new
     */
    public function add(Production $production, array $parsed, CatalogueSource $source, ?string $sourceUrl = null): int
    {
        $added = 0;
        $sort = (int) $production->credits()->max('sort');

        foreach ($parsed['credits'] as $credit) {
            $person = $this->person($production, $credit, $source, $sourceUrl);
            $character = $credit['character'] === null ? null : (People::clean($credit['character']) ?: null);
            $same = $production->credits()->where('person_id', $person->id)->where('role_type', $credit['role']);

            if ($credit['role'] === CreditRole::Actor) {
                // One actor credit per person and production: fill in the character if it was missing.
                $existing = $same->first();
                if ($existing !== null && $existing->character_name === null && $character !== null) {
                    $existing->update(['character_name' => $character]);
                }
                $exists = $existing !== null;
            } else {
                $exists = $same->exists();
            }

            if ($exists) {
                continue;
            }

            $production->credits()->create([
                'person_id' => $person->id,
                'role_type' => $credit['role'],
                'character_name' => $character,
                'sort' => ++$sort,
            ]);
            $added++;
        }

        if ($parsed['author'] !== null && blank($production->author)) {
            $production->update(['author' => $parsed['author']]);
        }

        return $added;
    }

    /**
     * The person for a credit: "#123" picks that person, "#нов" a new one (or the one this production
     * already has under that name and role, so pasting again doesn't create another), else by name.
     *
     * @param  ParsedCredit  $credit
     */
    private function person(Production $production, array $credit, CatalogueSource $source, ?string $sourceUrl): Person
    {
        $pick = $credit['pick'] ?? null;
        if (is_int($pick) && ($person = Person::find($pick)) !== null) {
            return $person;
        }
        if ($pick === 'new') {
            $existing = $production->credits()->where('role_type', $credit['role'])
                ->whereHas('person', fn ($q) => $q->where('name', People::clean($credit['name'])))
                ->first()?->person;

            return $existing ?? $this->people->create($credit['name'], $source, $sourceUrl);
        }

        return $this->people->findOrCreate($credit['name'], $source, $sourceUrl);
    }

    /** @return list<array{0: string, 1: ?string, 2: int|'new'|null}> name, character and "#…" pick */
    private static function names(string $text, bool $characters): array
    {
        $text = str_replace("\u{00A0}", ' ', $text);
        if (! $characters) {
            // "Донка Аврамова – Бочева" is one double surname.
            $text = preg_replace('/(\p{L})\s*[-–—]\s*(?=\p{Lu})/u', '$1-', $text) ?? $text;
        }

        $out = [];
        foreach (preg_split('/\s*[,;\/]\s*|\s+и\s+/u', $text) ?: [] as $part) {
            $character = null;
            $pick = null;
            if (preg_match('/\s*#(\d+|нов|new)(?![\p{L}\d])/u', $part, $m)) {
                $pick = ctype_digit($m[1]) ? (int) $m[1] : 'new';
                $part = str_replace($m[0], '', $part);
            }
            if ($characters && preg_match('/^(.+?)\s*\((.+)\)\s*$/u', $part, $m)) {
                [$part, $character] = [$m[1], $m[2]];
            } elseif ($characters && preg_match('/^(.+?)\s+[-–—]\s+(.+)$/u', $part, $m)) {
                [$part, $character] = [$m[1], $m[2]];
            }

            $name = self::name($part);
            if ($name !== null) {
                $out[] = [$name, $character, $pick];
            }
        }

        return $out;
    }

    /**
     * A person's name: titles dropped, then the leading capitalised words, 2 to 4 of them
     * ("Елизабет Динкова по романа на …" → "Елизабет Динкова"). Anything else isn't a name.
     */
    private static function name(string $text): ?string
    {
        $text = People::clean(preg_replace('/\([^)]*\)/u', '', $text) ?? $text);
        $text = preg_replace('/^(проф|доц|д-р|акад|гл\. ас)\.?\s+/iu', '', $text) ?? $text;
        $text = preg_replace('/^(от|по)\s+/u', '', $text) ?? $text; // "от Тони Макнамара"

        $words = [];
        foreach (preg_split('/\s+/u', $text) ?: [] as $word) {
            if (! preg_match('/^\p{Lu}[\p{L}\'’.-]*$/u', $word)) {
                break;
            }
            $words[] = $word;
        }

        return count($words) >= 2 && count($words) <= 4 ? implode(' ', $words) : null;
    }
}
