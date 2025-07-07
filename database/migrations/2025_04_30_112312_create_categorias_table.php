<?php

use App\Enums\EstadoGeneral;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->tinyInteger('nivel')->default(0)->comment('0: Principal, 1: Subcategoría L1, 2: Subcategoría L2');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
                        $table->boolean('activo')->default(true);
            $table->enum('estado', EstadoGeneral::values())->default(EstadoGeneral::Activo->value);;
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('categorias')->onDelete('cascade');
            $table->index(['parent_id', 'proveedor_id'], 'idx_parent_proveedor');
            // $table->check('nivel IN (0, 1, 2)');
        });
    }

    public function down()
    {
        Schema::dropIfExists('categorias');
    }
};
