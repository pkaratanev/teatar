<?php

namespace App\Services;

use App\Enums\CatalogueSource;
use App\Models\Person;
use Illuminate\Support\Str;

/**
 * One way to turn a name into a person, used by the importer, the cast reader and the
 * credits paste box: the same name (ignoring case, extra spaces and dash/quote styles)
 * is always the same person, so repeated or parallel runs don't create duplicates.
 */
class People
{
    public static function clean(string $name): string
    {
        $name = str_replace(["\u{00A0}", '–', '—'], [' ', '-', '-'], $name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return trim($name, " \t\n\r\0\x0B.,;:");
    }

    public function find(string $name): ?Person
    {
        $name = self::clean($name);

        // The oldest record wins, so a name always resolves to the same person.
        return Person::where('name', $name)->orderBy('id')->first()
            ?? Person::whereRaw('lower(name) = ?', [mb_strtolower($name)])->orderBy('id')->first();
    }

    public function findOrCreate(string $name, CatalogueSource $source, ?string $sourceUrl = null): Person
    {
        $name = self::clean($name);

        return $this->find($name) ?? $this->create($name, $source, $sourceUrl);
    }

    /** A new person even if the name exists (two people with the same name). */
    public function create(string $name, CatalogueSource $source, ?string $sourceUrl = null): Person
    {
        $name = self::clean($name);

        return Person::create([
            'name' => $name,
            'slug' => rtrim(Str::limit(Str::slug($name), 80, ''), '-').'-'.Str::lower(Str::random(4)),
            'source' => $source->value,
            'source_url' => $sourceUrl,
        ]);
    }
}
