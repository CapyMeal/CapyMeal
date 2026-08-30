<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable: las cuentas creadas vía Google no tienen contraseña
            // propia.
            $table->string('password')->nullable()->change();

            // Nullable + unique: null para cuentas password-only. Identifica
            // la cuenta de Google enlazada, ya sea por registro directo vía
            // Google o por auto-link a una cuenta existente con el mismo
            // email (Google sólo devuelve emails verificados, así que
            // enlazar automáticamente es seguro).
            $table->string('google_id')->nullable()->unique()->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
            // Si hay filas con password null (cuentas Google-only) al hacer
            // rollback, este ->change() falla -- esperado, no hay forma
            // segura de inventar una contraseña al bajar la migración.
            $table->string('password')->nullable(false)->change();
        });
    }
};
