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
        Schema::create('ordenes_compra_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_compra_id');
            $table->string('producto');
            $table->string('descripcion')->nullable();
            $table->decimal('cantidad', 10, 3);
            $table->string('unidad_medida')->nullable();
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('importe', 12, 2);
            $table->timestamps();

            // Índices
            $table->index('orden_compra_id');

            // Claves foráneas
            $table->foreign('orden_compra_id')->references('id')->on('ordenes_compra')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra_detalles');
    }
};
