<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The catalogue: theatres, their stages, productions, people, credits and
 * individual performances. Every catalogue row records where it came from
 * (source + source_url) so provenance stays auditable — see docs/plan.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theatres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city')->default('София')->index();
            $table->string('address')->nullable();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->string('website')->nullable();
            $table->string('box_office_url')->nullable();
            $table->string('phone')->nullable();
            // Which adapter class keeps this theatre's programme up to date (null = manual entry).
            $table->string('adapter')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('source')->default('admin');
            $table->string('source_url')->nullable();
            $table->timestamps();
        });

        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theatre_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();

            $table->unique(['theatre_id', 'name']);
        });

        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theatre_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->string('slug')->unique();
            $table->string('author')->nullable();
            $table->text('synopsis')->nullable();
            $table->string('age_advisory', 8)->nullable(); // e.g. "12+"
            $table->unsignedSmallInteger('running_time_minutes')->nullable();
            $table->date('premiered_on')->nullable();
            $table->string('status')->default('running')->index(); // App\Enums\ProductionStatus
            $table->string('source')->default('admin');
            $table->string('source_url')->nullable();
            // Denormalised for fast listing; recalculated when a rating changes.
            $table->unsignedInteger('ratings_count')->default(0);
            $table->decimal('rating_average', 3, 2)->nullable(); // in stars, 0.50–5.00
            $table->timestamps();

            $table->index(['theatre_id', 'status']);
        });

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->unsignedSmallInteger('born_year')->nullable();
            $table->string('source')->default('admin');
            $table->string('source_url')->nullable();
            $table->timestamps();
        });

        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('role_type'); // App\Enums\CreditRole
            $table->string('character_name')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['production_id', 'person_id', 'role_type', 'character_name']);
            $table->index(['person_id', 'role_type']);
        });

        Schema::create('performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at')->index();
            $table->string('ticket_url')->nullable();
            $table->decimal('price_from', 8, 2)->nullable(); // EUR
            $table->decimal('price_to', 8, 2)->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->string('source')->default('admin');
            $table->string('source_url')->nullable();
            $table->timestamps();

            // Adapters upsert on this key, so a nightly re-run never duplicates a show.
            $table->unique(['production_id', 'starts_at']);
        });

        Schema::create('adapter_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theatre_id')->constrained()->cascadeOnDelete();
            $table->string('adapter');
            $table->string('status'); // running | succeeded | failed
            $table->unsignedInteger('performances_found')->default(0);
            $table->unsignedInteger('performances_upserted')->default(0);
            $table->unsignedInteger('productions_created')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            // Fuzzy Cyrillic search on titles and names.
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX productions_title_trgm ON productions USING gin (title gin_trgm_ops)');
            DB::statement('CREATE INDEX people_name_trgm ON people USING gin (name gin_trgm_ops)');
            DB::statement('CREATE INDEX theatres_name_trgm ON theatres USING gin (name gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('adapter_runs');
        Schema::dropIfExists('performances');
        Schema::dropIfExists('credits');
        Schema::dropIfExists('people');
        Schema::dropIfExists('productions');
        Schema::dropIfExists('stages');
        Schema::dropIfExists('theatres');
    }
};
