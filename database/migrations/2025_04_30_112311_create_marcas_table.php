<?php

use App\Enums\EstadoGeneral;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('logo', 500)->nullable();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
                        $table->boolean('activo')->default(true);
            $table->enum('estado', EstadoGeneral::values())->default(EstadoGeneral::Activo->value);;
            $table->timestamps();

            $table->unique(['nombre', 'proveedor_id'], 'uk_marca_proveedor');
        });
    }

    public function down()
    {
        Schema::dropIfExists('marcas');
    }
};
