<?php

use App\Enums\EstadoGeneral;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lineas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->foreignId('marca_id')->constrained('marcas')->onDelete('cascade');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->boolean('activo')->default(true);
            $table->enum('estatus', EstadoGeneral::values())->default(EstadoGeneral::ACTIVO->value);
            $table->timestamps();

            $table->unique(['nombre', 'marca_id'], 'uk_linea_marca');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lineas');
    }
};
