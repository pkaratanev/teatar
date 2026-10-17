<?php

namespace App\Services;

use App\Enums\CatalogueSource;
use App\Enums\CreditRole;
use App\Models\Production;

/**
 * Credits from text, as a theatre's page or a person lists them:
 *
 *   Режисьор: Иван Петров
 *   Сценография и костюми: Мария Иванова
 *   Актьори: Анна Колева, Петър Стоянов (Хамлет)
 *   Актьори:
 *   Анна Колева - Офелия
 *
 * A known label starts a group; names follow on the same line (comma separated) or on the
 * next lines (one per line). A character goes in brackets or after " - ". Adding is safe to
 * repeat: an existing credit is never doubled and nothing is removed.
 */
class Credits
{
    /** Label (lower case, without the colon) → roles. Checked as "starts with". */
    private const LABELS = [
        'сценография и костюми' => [CreditRole::SetDesigner, CreditRole::CostumeDesigner],
        'сценограф и костюми' => [CreditRole::SetDesigner, CreditRole::CostumeDesigner],
        'художник' => [CreditRole::SetDesigner, CreditRole::CostumeDesigner],
        'режисьор' => [CreditRole::Director],
        'режисура' => [CreditRole::Director],
        'постановка' => [CreditRole::Director],
        'автор' => [CreditRole::Playwright],
        'драматург' => [CreditRole::Playwright],
        'пиеса' => [CreditRole::Playwright],
        'по пиесата на' => [CreditRole::Playwright],
        'превод' => [CreditRole::Translator],
        'сценография' => [CreditRole::SetDesigner],
        'сценограф' => [CreditRole::SetDesigner],
        'костюми' => [CreditRole::CostumeDesigner],
        'костюмограф' => [CreditRole::CostumeDesigner],
        'музика' => [CreditRole::Composer],
        'композитор' => [CreditRole::Composer],
        'хореография' => [CreditRole::Choreographer],
        'хореограф' => [CreditRole::Choreographer],
        'светлинен дизайн' => [CreditRole::LightingDesigner],
        'осветление' => [CreditRole::LightingDesigner],
        'светлини' => [CreditRole::LightingDesigner],
        'актьори' => [CreditRole::Actor],
        'участват' => [CreditRole::Actor],
        'в ролите' => [CreditRole::Actor],
        'играят' => [CreditRole::Actor],
        'актьорски състав' => [CreditRole::Actor],
    ];

    public function __construct(private People $people) {}

    /**
     * @return array{credits: list<array{role: CreditRole, name: string, character: ?string}>, skipped: list<string>}
     *               skipped: lines with a label we don't know, so their names were left out
     */
    public function parse(string $text): array
    {
        $credits = [];
        $skipped = [];
        $roles = null;

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $rest = $line;
            if (preg_match('/^([^:]{2,40}):\s*(.*)$/u', $line, $m)) {
                $roles = self::roles($m[1]);
                if ($roles === null) {
                    $skipped[] = $line;

                    continue;
                }
                $rest = $m[2];
            } elseif ($roles === null) {
                $skipped[] = $line;

                continue;
            }

            foreach (self::names($rest) as [$name, $character]) {
                foreach ($roles as $role) {
                    $credits[] = ['role' => $role, 'name' => $name, 'character' => $role === CreditRole::Actor ? $character : null];
                }
            }
        }

        return ['credits' => $credits, 'skipped' => $skipped];
    }

    /**
     * Adds the credits to the production. When a playwright is added and the production
     * has no author yet, the author field is filled too.
     *
     * @param  list<array{role: CreditRole, name: string, character: ?string}>  $credits
     * @return int how many credits were new
     */
    public function add(Production $production, array $credits, CatalogueSource $source, ?string $sourceUrl = null): int
    {
        $added = 0;
        $sort = (int) $production->credits()->max('sort');

        foreach ($credits as $credit) {
            $person = $this->people->findOrCreate($credit['name'], $source, $sourceUrl);
            $character = $credit['character'] === null ? null : People::clean($credit['character']);

            $same = $production->credits()->where('person_id', $person->id)->where('role_type', $credit['role']);
            if ($credit['role'] === CreditRole::Actor) {
                // One actor credit per person and production: fill in the character if it was missing.
                $existing = $same->first();
                if ($existing !== null && $existing->character_name === null && filled($character)) {
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
                'character_name' => $character ?: null,
                'sort' => ++$sort,
            ]);
            $added++;

            if ($credit['role'] === CreditRole::Playwright && blank($production->author)) {
                $production->update(['author' => $person->name]);
            }
        }

        return $added;
    }

    /** @return list<CreditRole>|null */
    private static function roles(string $label): ?array
    {
        $label = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $label) ?? $label));
        foreach (self::LABELS as $known => $roles) {
            if (str_starts_with($label, $known)) {
                return $roles;
            }
        }

        return null;
    }

    /** @return list<array{0: string, 1: ?string}> name and character */
    private static function names(string $text): array
    {
        $out = [];
        foreach (preg_split('/\s*[,;]\s*|\s+и\s+/u', $text) ?: [] as $part) {
            $character = null;
            if (preg_match('/^(.+?)\s*\((.+)\)\s*$/u', $part, $m)) {
                [$part, $character] = [$m[1], $m[2]];
            } elseif (preg_match('/^(.+?)\s+[-–—]\s+(.+)$/u', $part, $m)) {
                [$part, $character] = [$m[1], $m[2]];
            }

            $name = People::clean($part);
            // A name has at least two words; single words are usually leftovers ("и", "др.").
            if (mb_strlen($name) >= 4 && str_contains($name, ' ') && mb_strlen($name) <= 80) {
                $out[] = [$name, $character];
            }
        }

        return $out;
    }
}
