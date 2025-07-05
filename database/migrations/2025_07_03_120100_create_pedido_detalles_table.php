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
        Schema::create('pedido_detalles', function (Blueprint $table) {
            $table->id();

            // Relaciones 
            $table->foreignId('pedido_id')->constrained()->onDelete('cascade');
            $table->foreignId('cotizacion_detalle_id')->constrained()->onDelete('cascade');

            // Información del producto
            $table->integer('cantidad_confirmada');
            $table->decimal('precio_unitario_final', 10, 2);
            $table->decimal('subtotal', 12, 2);

            // Información adicional
            $table->decimal('descuento_unitario', 10, 2)->default(0);
            $table->decimal('descuento_total', 12, 2)->default(0);
            $table->text('observaciones')->nullable();

            // Control de entrega
            $table->integer('cantidad_entregada')->default(0);
            $table->integer('cantidad_pendiente')->default(0);
            $table->boolean('entrega_completa')->default(false);

            // Timestamps
            $table->timestamps();

            // Índices
            $table->index(['pedido_id']);
            $table->index(['cotizacion_detalle_id']);
            $table->index(['entrega_completa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_detalles');
    }
};
