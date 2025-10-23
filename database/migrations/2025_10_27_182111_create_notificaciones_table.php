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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50); // 'nueva_orden_compra', 'orden_actualizada', etc.
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->string('titulo', 255);
            $table->text('mensaje');
            $table->json('data')->nullable(); // Datos adicionales de la notificación
            $table->boolean('leida')->default(false);
            $table->timestamps();
            
            // Índices para mejorar performance
            $table->index(['proveedor_id', 'leida', 'created_at']);
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
