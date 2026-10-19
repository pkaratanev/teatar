<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Merges made on 2026-10-19 before aliases existed, whose removed spelling is known (agent report).
 * Skipped when the kept person isn't there under that name.
 */
return new class extends Migration
{
    private const ALIASES = [
        'Любо Нейков' => 'Любомир Нейков',
        'Юлияна Войкова-Найман' => 'Юлиана Войкова-Найман',
    ];

    public function up(): void
    {
        foreach (self::ALIASES as $alias => $name) {
            $personId = DB::table('people')->where('name', $name)->orderBy('id')->value('id');
            $key = mb_strtolower($alias);
            if ($personId !== null && ! DB::table('person_aliases')->where('name_key', $key)->exists()) {
                DB::table('person_aliases')->insert([
                    'person_id' => $personId, 'name' => $alias, 'name_key' => $key,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('person_aliases')->whereIn('name_key', array_map('mb_strtolower', array_keys(self::ALIASES)))->delete();
    }
};
