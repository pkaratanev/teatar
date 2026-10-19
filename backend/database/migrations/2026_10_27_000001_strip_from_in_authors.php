<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Imported authors written as "от Франсис Вебер" lose the "от" (the importer no longer adds it). */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('productions')->where('author', 'like', 'от %')->orderBy('id')->each(function (object $row) {
            DB::table('productions')->where('id', $row->id)->update(['author' => mb_substr($row->author, 3)]);
        });
    }

    public function down(): void
    {
        // Nothing to undo.
    }
};
