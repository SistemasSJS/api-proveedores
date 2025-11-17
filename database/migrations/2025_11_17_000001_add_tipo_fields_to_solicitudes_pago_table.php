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
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->string('tipo')->nullable()->after('verificada');
            $table->integer('tipo_id')->nullable()->after('tipo');
            $table->integer('obra_id')->nullable()->after('tipo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'tipo_id', 'obra_id']);
        });
    }
};
