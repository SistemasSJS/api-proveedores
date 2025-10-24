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
            // Campos de sincronización con API Construcciones
            $table->integer('obra_id')->nullable()->after('empresa_construcc_id');
            $table->integer('usuario_id')->nullable()->after('obra_id');
            $table->string('tipo_orden', 50)->nullable()->after('usuario_id');
            $table->integer('requisicion_id')->nullable()->after('tipo_orden');
            $table->boolean('tiene_requisicion')->default(false)->after('requisicion_id');
            $table->decimal('subtotal', 15, 2)->nullable()->after('tiene_requisicion');
            $table->decimal('iva', 15, 2)->nullable()->after('subtotal');
            $table->decimal('tasa', 5, 2)->nullable()->after('iva');
            $table->string('estatus_construcc', 50)->nullable()->after('estado')->comment('Estatus original de API Construcciones');

            // Índices para mejorar búsquedas
            $table->index('obra_id');
            $table->index('requisicion_id');
            $table->index(['proveedor_id', 'estatus_construcc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropIndex(['ordenes_compra_obra_id_index']);
            $table->dropIndex(['ordenes_compra_requisicion_id_index']);
            $table->dropIndex(['ordenes_compra_proveedor_id_estatus_construcc_index']);

            $table->dropColumn([
                'obra_id',
                'usuario_id',
                'tipo_orden',
                'requisicion_id',
                'tiene_requisicion',
                'subtotal',
                'iva',
                'tasa',
                'estatus_construcc',
            ]);
        });
    }
};
