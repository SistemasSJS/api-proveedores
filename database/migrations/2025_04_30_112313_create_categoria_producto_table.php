<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('categoria_producto', function (Blueprint $table) {
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->primary(['producto_id', 'categoria_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('categoria_producto');
    }
};
