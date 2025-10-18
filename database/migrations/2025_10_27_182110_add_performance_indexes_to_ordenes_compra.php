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
        // Índices para tabla ordenes_compra
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->index(['proveedor_id', 'estado'], 'idx_ordenes_proveedor_estado');
            $table->index(['fecha_orden'], 'idx_ordenes_fecha');
            $table->index(['estado'], 'idx_ordenes_estado');
            $table->index(['numero_orden'], 'idx_ordenes_numero');
            $table->index(['created_at'], 'idx_ordenes_created');
        });

        // Índices para tabla ordenes_compra_detalles
        Schema::table('ordenes_compra_detalles', function (Blueprint $table) {
            $table->index(['orden_compra_id'], 'idx_detalles_orden');
            // $table->index(['producto_id'], 'idx_detalles_producto');
            // $table->index(['orden_compra_id', 'producto_id'], 'idx_detalles_orden_producto');
        });

        // Índices para tabla orden_compra_solicitud_pago
        Schema::table('orden_compra_solicitud_pago', function (Blueprint $table) {
            $table->index(['orden_compra_id'], 'idx_pivot_orden');
            $table->index(['solicitud_pago_id'], 'idx_pivot_solicitud');
        });

        // Índices para tabla solicitudes_pago
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->index(['estado_solicitud'], 'idx_solicitudes_estado');
            $table->index(['fecha_registro_pendiente'], 'idx_solicitudes_proveedor');
            $table->index(['fecha_registro_pendiente'], 'idx_solicitudes_fecha');
            $table->index(['created_at'], 'idx_solicitudes_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices de tabla ordenes_compra
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropIndex('idx_ordenes_proveedor_estado');
            $table->dropIndex('idx_ordenes_fecha');
            $table->dropIndex('idx_ordenes_estado');
            $table->dropIndex('idx_ordenes_numero');
            $table->dropIndex('idx_ordenes_created');
        });

        // Eliminar índices de tabla ordenes_compra_detalles
        Schema::table('ordenes_compra_detalles', function (Blueprint $table) {
            $table->dropIndex('idx_detalles_orden');
            $table->dropIndex('idx_detalles_producto');
            $table->dropIndex('idx_detalles_orden_producto');
        });

        // Eliminar índices de tabla orden_compra_solicitud_pago
        Schema::table('orden_compra_solicitud_pago', function (Blueprint $table) {
            $table->dropIndex('idx_pivot_orden');
            $table->dropIndex('idx_pivot_solicitud');
        });

        // Eliminar índices de tabla solicitudes_pago
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropIndex('idx_solicitudes_estado');
            $table->dropIndex('idx_solicitudes_proveedor');
            $table->dropIndex('idx_solicitudes_fecha');
            $table->dropIndex('idx_solicitudes_created');
        });
    }
};
