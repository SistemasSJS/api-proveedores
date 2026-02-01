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
        Schema::table('pagos_spp', function (Blueprint $table) {
            // Nueva columna para relacionar el pago con un proveedor específico
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();

            // Indice para optimizar consultas por proveedor
            $table->index('proveedor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos_spp', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
            $table->dropIndex(['proveedor_id']);
            $table->dropColumn('proveedor_id');
        });
    }
};
