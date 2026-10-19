<?php

use App\Models\Theatre;
use App\Support\TheatreContacts;
use Illuminate\Database\Migrations\Migration;

/**
 * Address, coordinates, box-office phone and ticket page for the launch theatres already in the database.
 * Only empty fields are filled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Theatre::query()->each(fn (Theatre $theatre) => TheatreContacts::fill($theatre));
    }

    public function down(): void
    {
        // Data only; nothing to undo.
    }
};
