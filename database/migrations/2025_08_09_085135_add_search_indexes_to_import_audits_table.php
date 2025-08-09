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
        Schema::table('import_audits', function (Blueprint $table) {
            // Agregar índices para optimizar búsquedas y filtros
            $table->index(['proveedor_id', 'tipo'], 'idx_import_audits_proveedor_tipo');
            $table->index(['proveedor_id', 'estado'], 'idx_import_audits_proveedor_estado');
            $table->index(['proveedor_id', 'created_at'], 'idx_import_audits_proveedor_fecha');
            $table->index(['estado', 'created_at'], 'idx_import_audits_estado_fecha');
            $table->index('total_registros', 'idx_import_audits_total_registros');
            $table->index('errores', 'idx_import_audits_errores');
            
            // Mejorar el índice del job_id para que sea nullable si es necesario
            // (En caso de que no todos los imports tengan job_id)
            $table->string('job_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_audits', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex('idx_import_audits_proveedor_tipo');
            $table->dropIndex('idx_import_audits_proveedor_estado');
            $table->dropIndex('idx_import_audits_proveedor_fecha');
            $table->dropIndex('idx_import_audits_estado_fecha');
            $table->dropIndex('idx_import_audits_total_registros');
            $table->dropIndex('idx_import_audits_errores');
            
            // Revertir job_id a no nullable
            $table->string('job_id')->nullable(false)->change();
        });
    }
};
