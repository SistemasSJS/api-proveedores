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
        Schema::table('ordenes_compra', function (Blueprint $table) {
            // Solo actualizar columnas existentes para permitir NULL
            $nullableColumns = [
                'numero_orden',
                'fecha_orden',
                'proveedor_id',
                'empresa_construcc_id',
                'importe_total',
                'estado',
                'fecha_aprobacion',
                'observaciones',
                'metadata_json',
                'monto_sp_asociado',
                'sp_count',
                'obra_id',
                'usuario_id',
                'tipo_orden',
                'requisicion_id',
                'tiene_requisicion',
                'subtotal',
                'iva',
                'tasa',
                'estatus_construcc',
            ];

            foreach ($nullableColumns as $col) {
                if (Schema::hasColumn('ordenes_compra', $col)) {
                    try {
                        $table->string($col)->nullable()->change();
                    } catch (\Throwable $e) {
                        // Si no es string, intentamos otros tipos comunes
                        try {
                            $table->decimal($col, 12, 2)->nullable()->change();
                        } catch (\Throwable $e2) {
                            try {
                                $table->integer($col)->nullable()->change();
                            } catch (\Throwable $e3) {
                                try {
                                    $table->boolean($col)->nullable()->change();
                                } catch (\Throwable $e4) {
                                    // Ignorar si no aplica
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            // Revertir a columnas NOT NULL cuando sea posible
            $notNullableColumns = [
                'numero_orden',
                'fecha_orden',
                'proveedor_id',
                'empresa_construcc_id',
                'importe_total',
                'estado',
            ];

            foreach ($notNullableColumns as $col) {
                if (Schema::hasColumn('ordenes_compra', $col)) {
                    try {
                        $table->string($col)->nullable(false)->change();
                    } catch (\Throwable $e) {
                        try {
                            $table->decimal($col, 12, 2)->nullable(false)->change();
                        } catch (\Throwable $e2) {
                            try {
                                $table->integer($col)->nullable(false)->change();
                            } catch (\Throwable $e3) {
                                // Ignorar si no aplica
                            }
                        }
                    }
                }
            }
        });
    }
};
