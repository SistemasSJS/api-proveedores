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
            // Agregar campo para empresa (referencia a empresa_construcc)
            $table->unsignedBigInteger('empresa_construcc_id')->nullable()->after('proveedor_id');
            
            // Agregar campo para residente
            $table->string('residente', 255)->nullable()->after('empresa_construcc_id');
            
            // Agregar campo para cotización (referencia a cotizaciones si existe esa tabla)
            $table->unsignedBigInteger('cotizacion_id')->nullable()->after('residente');
            
            // Índices para mejorar performance
            $table->index(['empresa_construcc_id']);
            $table->index(['cotizacion_id']);
            
            // Foreign key para empresa_construcc
            $table->foreign('empresa_construcc_id')
                  ->references('id')
                  ->on('empresa_construcc')
                  ->onDelete('set null');
                  
            // Nota: La foreign key para cotizacion_id se agregará cuando existe la tabla cotizaciones
            // $table->foreign('cotizacion_id')
            //       ->references('id')
            //       ->on('cotizaciones')
            //       ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            // Eliminar foreign keys primero
            $table->dropForeign(['empresa_construcc_id']);
            // $table->dropForeign(['cotizacion_id']); // Descomentar cuando exista la tabla cotizaciones
            
            // Eliminar columnas
            $table->dropColumn(['empresa_construcc_id', 'residente', 'cotizacion_id']);
        });
    }
};
