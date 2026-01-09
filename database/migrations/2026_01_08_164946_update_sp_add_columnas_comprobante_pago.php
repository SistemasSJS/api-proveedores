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
            $table->string('nombre_beneficiario_pago')->nullable()->default(null)->after('item_visto');
            $table->string('clave_rastreo_pago')->nullable()->default(null)->after('nombre_beneficiario_pago');
            $table->string('banco_pago')->nullable()->default(null)->after('clave_rastreo_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn('nombre_beneficiario_pago');
            $table->dropColumn('clave_rastreo_pago');
            $table->dropColumn('banco_pago');
        });
    }
};
