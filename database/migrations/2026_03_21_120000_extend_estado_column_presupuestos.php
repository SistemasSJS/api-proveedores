<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extiende la columna estado para soportar 'rechazado_con_observacion' (25 chars).
     */
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->string('estado', 30)->default('borrador')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->string('estado', 20)->default('borrador')->change();
        });
    }
};
