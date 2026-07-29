<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Limpia registros sin dueño (solo en desarrollo)
        DB::table('meal_entries')->delete();

        Schema::table('meal_entries', function (Blueprint $table) {
            $table->dropUnique(['date']);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('meal_entries', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'date']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique('date');
        });
    }
};
