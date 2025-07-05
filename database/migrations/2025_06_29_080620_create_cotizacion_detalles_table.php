<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cotizacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');

            $table->foreignId('requisicion_detalle_id')->constrained('requisicion_productos')->nullOnDelete('cascade');
            $table->integer('cantidad_cotizada');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->integer('tiempo_entrega_dias');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['cotizacion_id', 'producto_id']);
            $table->index(['requisicion_detalle_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cotizacion_detalles');
    }
};
