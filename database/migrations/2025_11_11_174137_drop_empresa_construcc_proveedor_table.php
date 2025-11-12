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
        Schema::dropIfExists('empresa_construcc_proveedor');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recrear la tabla en caso de rollback
        Schema::create('empresa_construcc_proveedor', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_construcc_id')
                ->constrained('empresa_construcc')
                ->cascadeOnDelete();

            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('usuario_construcc_id')->nullable();
            $table->string('usuario_construcc_nombre')->nullable();

            // Índices regulares (NO UNIQUE)
            $table->index(['empresa_construcc_id', 'proveedor_id'], 'idx_empresa_proveedor');
            $table->index('usuario_construcc_id');
            $table->index(['empresa_construcc_id', 'proveedor_id', 'usuario_construcc_id'], 'idx_empresa_proveedor_usuario');

            $table->timestamps();
        });
    }
};
