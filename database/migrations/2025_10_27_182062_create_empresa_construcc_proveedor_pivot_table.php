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

            // Evitar duplicados
            $table->unique(['empresa_construcc_id', 'proveedor_id'], 'empresa_proveedor_unique');

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
