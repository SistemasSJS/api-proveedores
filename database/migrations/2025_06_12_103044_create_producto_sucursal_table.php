<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
    {
        Schema::create('producto_sucursal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->foreignId('sucursal_id')->constrained()->onDelete('cascade');
            $table->integer('stock_local')->default(0);
            $table->decimal('precio_local', 10, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->unique(['producto_id', 'sucursal_id']);
            $table->index(['sucursal_id', 'activo']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('producto_sucursal');
    }
};
