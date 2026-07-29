<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_entries', function (Blueprint $table) {
            // Elimina el índice único en date (era global, ahora es por usuario)
            $table->dropUnique(['date']);

            $table->foreignId('user_id')
                ->default(1)
                ->constrained()
                ->cascadeOnDelete();

            // Un usuario solo puede tener un registro por fecha
            $table->unique(['user_id', 'date']);
        });

        // Elimina el default una vez que la columna existe
        Schema::table('meal_entries', function (Blueprint $table) {
            $table->foreignId('user_id')->default(null)->change();
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
