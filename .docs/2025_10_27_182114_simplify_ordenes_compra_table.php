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
        Schema::table('ordenes_compra', function (Blueprint $table) {
            // 🔹 Primero eliminar claves foráneas que impiden modificar
            if (Schema::hasColumn('ordenes_compra', 'proveedor_id')) {
                try {
                    $table->dropForeign('ordenes_compra_proveedor_id_foreign');
                } catch (\Throwable $e) {
                    // Ignorar si no existe
                }
            }

            if (Schema::hasColumn('ordenes_compra', 'empresa_construcc_id')) {
                try {
                    $table->dropForeign('ordenes_compra_empresa_construcc_id_foreign');
                } catch (\Throwable $e) {
                    // Ignorar si no existe
                }
            }
        });

        // 🔹 Luego cambiar las columnas a NULLABLE
        Schema::table('ordenes_compra', function (Blueprint $table) {
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

        // 🔹 Finalmente, restaurar las foreign keys eliminadas
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_compra', 'proveedor_id')) {
                try {
                    $table->foreign('proveedor_id', 'ordenes_compra_proveedor_id_foreign')
                        ->references('id')
                        ->on('proveedores')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // Ignorar si ya existe
                }
            }

            if (Schema::hasColumn('ordenes_compra', 'empresa_construcc_id')) {
                try {
                    $table->foreign('empresa_construcc_id', 'ordenes_compra_empresa_construcc_id_foreign')
                        ->references('id')
                        ->on('empresa_construcc')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // Ignorar si ya existe
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
            // Restaurar a NOT NULL
            $notNullable = [
                'proveedor_id',
                'empresa_construcc_id',
                'importe_total',
                'estado',
            ];

            foreach ($notNullable as $col) {
                if (Schema::hasColumn('ordenes_compra', $col)) {
                    try {
                        $table->integer($col)->nullable(false)->change();
                    } catch (\Throwable $e) {
                        // Ignorar si no aplica
                    }
                }
            }
        });
    }
};
    