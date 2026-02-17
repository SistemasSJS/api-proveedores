<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agregar índices para optimizar el reporte de contabilidad
     */
    public function up(): void
    {
        Schema::connection('mysql5')->table('pagos_spp', function (Blueprint $table) {
            // Índice para filtrar por cuenta bancaria (banco_id en el reporte)
            $table->index('cuenta_bancaria_empresa_construcc_id', 'idx_cuenta_bancaria_empresa');
            
            // Índice compuesto para el filtro principal del reporte: fecha_pago + banco
            $table->index(['fecha_pago', 'cuenta_bancaria_empresa_construcc_id'], 'idx_fecha_banco');
        });

        // Agregar índice en folio_factura para búsquedas más rápidas en solicitudes_pago
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {
            $table->index('folio_factura', 'idx_folio_factura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql5')->table('pagos_spp', function (Blueprint $table) {
            $table->dropIndex('idx_cuenta_bancaria_empresa');
            $table->dropIndex('idx_fecha_banco');
        });

        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {
            $table->dropIndex('idx_folio_factura');
        });
    }
};
