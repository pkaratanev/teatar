<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Manual review of productions (admin "За преглед"): an importer flags a production it creates or adds data to;
 * an admin checks it and marks it reviewed. Every production the importers already made starts flagged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->boolean('needs_review')->default(false)->index();
            $table->string('review_reason', 16)->nullable(); // new | changed
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('productions')->where('source', 'adapter')->update(['needs_review' => true, 'review_reason' => 'new']);
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['needs_review', 'review_reason', 'reviewed_at']);
        });
    }
};
