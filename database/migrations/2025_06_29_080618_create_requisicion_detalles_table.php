<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up()
    {
        Schema::create('requisicion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisicion_id')->constrained('requisiciones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->integer('cantidad');
            $table->decimal('precio_unitario_estimado', 10, 2);
            $table->decimal('subtotal_estimado', 12, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->index(['requisicion_id']);
            $table->index(['producto_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('requisicion_detalles');
    }
};
