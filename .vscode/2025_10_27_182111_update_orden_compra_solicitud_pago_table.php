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
        Schema::table('orden_compra_solicitud_pago', function (Blueprint $table) {
            // 🔹 Cambiar columnas si existen
            if (Schema::hasColumn('orden_compra_solicitud_pago', 'monto_asociado')) {
                $table->decimal('monto_asociado', 12, 2)->nullable()->change();
            }

            if (Schema::hasColumn('orden_compra_solicitud_pago', 'fecha_vinculacion')) {
                $table->timestamp('fecha_vinculacion')->useCurrent()->change();
            }

            // 🔹 Eliminar índices antiguos sin romper si no existen
            foreach (['oc_sp_oc_id_idx', 'oc_sp_sp_id_idx', 'oc_sp_composite_idx'] as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Throwable $e) {
                    // Ignorar si no existe
                }
            }

            // 🔹 Asegurar índice único correcto
            $table->unique(['orden_compra_id', 'solicitud_pago_id'], 'oc_sp_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_compra_solicitud_pago', function (Blueprint $table) {
            // Revertir cambios
            $table->dateTime('fecha_vinculacion')->change();
            $table->decimal('monto_asociado', 12, 2)->nullable(false)->change();

            // Recrear índices antiguos
            $table->index(['orden_compra_id', 'solicitud_pago_id'], 'oc_sp_composite_idx');
            $table->index('orden_compra_id', 'oc_sp_oc_id_idx');
            $table->index('solicitud_pago_id', 'oc_sp_sp_id_idx');
        });
    }
};
