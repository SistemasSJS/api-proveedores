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
            // Campos para estadísticas detalladas por tipo de error
            $table->json('error_types')->nullable()->after('errores_detalle')
                ->comment('Array de tipos de errores únicos encontrados durante la importación');
            
            // Campos para métricas de rendimiento
            $table->decimal('processing_time', 8, 2)->nullable()->after('error_types')
                ->comment('Tiempo total de procesamiento en segundos');
                
            $table->decimal('memory_usage', 8, 2)->nullable()->after('processing_time')
                ->comment('Uso de memoria peak en MB');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_audits', function (Blueprint $table) {
            $table->dropColumn([
                'error_types',
                'processing_time', 
                'memory_usage'
            ]);
        });
    }
};
