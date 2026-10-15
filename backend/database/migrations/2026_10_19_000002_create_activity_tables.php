<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What users do: rate, review, log visits, keep a watchlist, follow, like and report.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            // Half-star units: 1 = ½★, 10 = ★★★★★.
            $table->unsignedTinyInteger('half_stars');
            $table->timestamps();

            // One rating per user per production is enforced by the database.
            $table->unique(['user_id', 'production_id']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('has_spoilers')->default(false);
            $table->string('status')->default('visible')->index(); // App\Enums\ReviewStatus
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();

            $table->index(['production_id', 'status', 'created_at']);
        });

        Schema::create('diary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_id')->nullable()->constrained()->nullOnDelete();
            $table->date('seen_on');
            $table->timestamps();

            // Several visits to the same production are allowed, one per day.
            $table->unique(['user_id', 'production_id', 'seen_on']);
            $table->index(['user_id', 'seen_on']);
        });

        Schema::create('watchlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'production_id']);
        });

        // A user follows another user or a person (actor, director …).
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('followable');
            $table->timestamps();

            $table->unique(['user_id', 'followable_type', 'followable_id']);
        });

        Schema::create('review_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'review_id']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->string('reason'); // spam | abuse | spoiler | other
            $table->text('note')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['reporter_id', 'review_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_half_stars_range CHECK (half_stars BETWEEN 1 AND 10)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('review_likes');
        Schema::dropIfExists('follows');
        Schema::dropIfExists('watchlist_items');
        Schema::dropIfExists('diary_entries');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('ratings');
    }
};
