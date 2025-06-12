<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->string('url_imagen');
            $table->string('alt_text')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('es_principal')->default(false);
            $table->timestamps();

            $table->index(['producto_id', 'orden']);
            $table->index(['producto_id', 'es_principal']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('producto_imagenes');
    }
};
