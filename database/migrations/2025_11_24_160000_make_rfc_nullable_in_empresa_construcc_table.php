<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empresa_construcc', function (Blueprint $table) {
            // Permitir valores nulos en RFC (el índice UNIQUE ya fue removido en una migración previa)
            $table->string('rfc', 13)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_construcc', function (Blueprint $table) {
            // Volver a hacer obligatoria la columna RFC (sin restaurar UNIQUE)
            $table->string('rfc', 13)->nullable(false)->change();
        });
    }
};
