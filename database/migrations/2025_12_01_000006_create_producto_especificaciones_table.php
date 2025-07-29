<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::create('producto_especificaciones', function (Blueprint $table) {
      $table->id();
      $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
      $table->string('atributo');
      $table->text('valor');
      $table->string('unidad')->nullable();
      $table->integer('orden')->default(0);
      $table->timestamps();

      $table->index(['producto_id', 'orden']);
    });
  }

  public function down()
  {
    Schema::dropIfExists('producto_especificaciones');
  }
};
