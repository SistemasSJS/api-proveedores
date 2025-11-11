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
        // Crear tabla pivote para relación muchos a muchos
        Schema::create('empresa_construcc_proveedor', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_construcc_id')
                ->constrained('empresa_construcc')
                ->cascadeOnDelete();

            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->cascadeOnDelete();

            // Índice para búsquedas (sin UNIQUE para permitir múltiples usuarios)
            $table->index(['empresa_construcc_id', 'proveedor_id'], 'idx_empresa_proveedor');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa_construcc_proveedor');
    }
};
