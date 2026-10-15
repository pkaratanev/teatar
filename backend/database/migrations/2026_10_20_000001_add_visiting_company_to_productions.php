<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guest performances: a visiting company plays at the host theatre
 * ("Бариерата - Гостува ДТ-Пловдив"). The production sits under the host
 * theatre, which is where the audience goes and buys tickets.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->string('visiting_company')->nullable()->after('author');
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn('visiting_company');
        });
    }
};
