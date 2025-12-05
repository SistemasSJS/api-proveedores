<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            // Quitar la restricción UNIQUE sobre razon_social para permitir duplicados
            $table->dropUnique(['razon_social']);
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            // Restaurar la restricción UNIQUE sobre razon_social
            $table->unique('razon_social');
        });
    }
};
