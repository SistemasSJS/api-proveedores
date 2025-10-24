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
            // Agregar nuevos campos simplificados
            $table->string('orden_compra_id')->nullable()->after('proveedor_id');
            $table->string('estatus')->default('pendiente')->after('orden_compra_id');
            
            // Índices para búsquedas eficientes
            $table->index('estatus');
            
            // Claves foráneas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            // Eliminar claves foráneas
            
            // Eliminar índices
            $table->dropIndex(['estatus']);
            
            // Eliminar campos
            $table->dropColumn(['orden_compra_id', 'estatus']);
        });
    }
};
           