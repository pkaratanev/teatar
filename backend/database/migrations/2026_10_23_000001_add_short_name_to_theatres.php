<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A short name for chips and tight spaces: "Народен театър", not "Иван Вазов" (the quoted part of the name).
 * Filled here for the launch theatres already in a database; editable in the admin.
 */
return new class extends Migration
{
    public const SHORT_NAMES = [
        'Театър „Българска армия“' => 'Българска армия',
        'Народен театър „Иван Вазов“' => 'Народен театър',
        'Открита сцена „Сълза и смях“' => 'Сълза и смях',
        'Младежки театър „Николай Бинев“' => 'Младежки театър',
        'Театър 199 „Валентин Стойчев“' => 'Театър 199',
        'Театрална работилница „Сфумато“' => 'Сфумато',
        'Сатиричен театър „Алеко Константинов“' => 'Сатирата',
        'Малък градски театър „Зад канала“' => 'Зад канала',
        'Театър „София“' => 'Театър „София“',
        'Театър „Възраждане“' => 'Възраждане',
        'Нов театър НДК' => 'Нов театър НДК',
        'Théatro' => 'Théatro',
        'Драматичен театър „Стоян Бъчваров“' => 'Драматичен театър',
        'Държавен куклен театър – Варна' => 'Куклен театър',
    ];

    public function up(): void
    {
        Schema::table('theatres', function (Blueprint $table) {
            $table->string('short_name', 40)->nullable()->after('name');
        });

        foreach (self::SHORT_NAMES as $name => $short) {
            DB::table('theatres')->where('name', $name)->whereNull('short_name')->update(['short_name' => $short]);
        }
    }

    public function down(): void
    {
        Schema::table('theatres', function (Blueprint $table) {
            $table->dropColumn('short_name');
        });
    }
};
