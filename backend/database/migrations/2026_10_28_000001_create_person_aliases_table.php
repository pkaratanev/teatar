<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Other spellings of a person's name, remembered when a duplicate is merged into them, so the
 * next import that meets "Любо Нейков" finds "Любомир Нейков" instead of creating him again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->text('name');
            $table->string('name_key', 191)->unique(); // lower case, cleaned (App\Services\People::key)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_aliases');
    }
};
