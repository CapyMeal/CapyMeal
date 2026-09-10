<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Las tres van encriptadas (cast 'encrypted' en User.php) --
            // acá quedan como text, no string, porque el valor cifrado es
            // más largo que el original.
            $table->text('two_factor_secret')->nullable()->after('avatar');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');

            // Única fuente de verdad de "2FA está activo": null significa
            // que nunca se activó, o que setup() generó un secreto pero
            // todavía no se confirmó con un código real (ver
            // TwoFactorController).
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
