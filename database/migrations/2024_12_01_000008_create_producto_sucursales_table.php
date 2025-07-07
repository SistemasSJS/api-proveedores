<?php

use App\Enums\EstadoGeneral;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::create('producto_sucursales', function (Blueprint $table) {
      $table->id();
      $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
      $table->foreignId('sucursal_id')->constrained('sucursales')->onDelete('cascade');
      $table->integer('stock_local')->default(0);
      $table->decimal('precio_local', 10, 2)->nullable();
      $table->boolean('activo')->default(true);
      $table->enum('estatus', EstadoGeneral::values())->default(EstadoGeneral::ACTIVO->value);;
      $table->timestamps();

      $table->unique(['producto_id', 'sucursal_id'], 'uk_producto_sucursal');
    });
  }

  public function down()
  {
    Schema::dropIfExists('producto_sucursales');
  }
};
