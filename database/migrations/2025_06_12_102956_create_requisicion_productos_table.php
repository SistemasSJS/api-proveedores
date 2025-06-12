<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('requisicion_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisicion_id')->constrained('requisiciones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2)->nullable();
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->text('especificaciones_adicionales')->nullable();
            $table->timestamps();

            $table->unique(['requisicion_id', 'producto_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('requisicion_productos');
    }
};
