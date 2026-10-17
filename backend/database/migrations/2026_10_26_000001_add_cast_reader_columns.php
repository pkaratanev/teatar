<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The cast reader: when a production's page was last read, and how many credits a sync added. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->timestamp('credits_checked_at')->nullable();
        });
        Schema::table('adapter_runs', function (Blueprint $table) {
            $table->unsignedInteger('credits_added')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('productions', fn (Blueprint $table) => $table->dropColumn('credits_checked_at'));
        Schema::table('adapter_runs', fn (Blueprint $table) => $table->dropColumn('credits_added'));
    }
};
