<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sync management: runs record moved and cancelled performances, and a small key/value
 * settings table holds the nightly sync time and the pause switch (edited in the admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adapter_runs', function (Blueprint $table) {
            $table->unsignedInteger('performances_moved')->default(0)->after('performances_upserted');
            $table->unsignedInteger('performances_cancelled')->default(0)->after('performances_moved');
            $table->index(['theatre_id', 'started_at']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');

        Schema::table('adapter_runs', function (Blueprint $table) {
            $table->dropIndex(['theatre_id', 'started_at']);
            $table->dropColumn(['performances_moved', 'performances_cancelled']);
        });
    }
};
