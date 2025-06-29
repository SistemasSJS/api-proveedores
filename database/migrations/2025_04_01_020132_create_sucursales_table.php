<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained()->onDelete('cascade');
            $table->string('nombre', 100);
            $table->text('direccion');
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('encargado', 100)->nullable();
            $table->boolean('activa')->default(true);
            $table->decimal('coordenadas_lat', 10, 8)->nullable();
            $table->decimal('coordenadas_lng', 11, 8)->nullable();
            $table->timestamps();

            $table->index(['proveedor_id', 'activa']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sucursales');
    }
};
