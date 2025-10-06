<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero, eliminamos el índice único existente sobre 'rfc'
        Schema::table('empresa_construcc', function (Blueprint $table) {
            $table->dropUnique(['rfc']); // Elimina la restricción UNIQUE
        });

        // Luego, redefinimos la columna si queremos asegurarnos de que siga siendo string (opcional)
        Schema::table('empresa_construcc', function (Blueprint $table) {
            $table->string('rfc', 13)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // En caso de rollback, restauramos el índice único
        Schema::table('empresa_construcc', function (Blueprint $table) {
            $table->unique('rfc');
        });
    }
};
