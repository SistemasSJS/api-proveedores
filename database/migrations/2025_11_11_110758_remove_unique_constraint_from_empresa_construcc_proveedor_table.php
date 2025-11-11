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
        Schema::table('empresa_construcc_proveedor', function (Blueprint $table) {
            // Simplemente agregar índice para consultas rápidas por usuario
            // No hacemos UNIQUE porque queremos permitir múltiples usuarios por empresa-proveedor
            $table->index(['empresa_construcc_id', 'proveedor_id', 'usuario_construcc_id'], 'idx_empresa_proveedor_usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_construcc_proveedor', function (Blueprint $table) {
            $table->dropIndex('idx_empresa_proveedor_usuario');
        });
    }
};
