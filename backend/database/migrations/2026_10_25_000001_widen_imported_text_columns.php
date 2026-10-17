<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Postgres (production) rejects text longer than a varchar column; SQLite (local) never did, so a long
 * ticket URL or title from a theatre site only failed on Laravel Cloud. Columns filled from outside
 * sources become text. Only widens: no data changes, nothing to undo.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private const NULLABLE = [
        'theatres' => ['address', 'website', 'box_office_url', 'source_url'],
        'productions' => ['title_en', 'author', 'visiting_company', 'source_url'],
        'people' => ['source_url'],
        'credits' => ['character_name'],
        'performances' => ['ticket_url', 'source_url'],
    ];

    /** @var array<string, list<string>> */
    private const REQUIRED = [
        'productions' => ['title'],
        'people' => ['name'],
    ];

    public function up(): void
    {
        foreach (self::NULLABLE as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                foreach ($columns as $column) {
                    $t->text($column)->nullable()->change();
                }
            });
        }

        foreach (self::REQUIRED as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                foreach ($columns as $column) {
                    $t->text($column)->change();
                }
            });
        }
    }

    public function down(): void
    {
        // Narrowing back could cut existing values; left as text.
    }
};
