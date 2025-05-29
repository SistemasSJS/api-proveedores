<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductosTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('modelo_codigo');
            $table->text('descripcion')->nullable();
            $table->string('sku')->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');
            $table->foreignId('marca_id')->nullable()->constrained('marcas')->onDelete('set null');
            $table->foreignId('linea_id')->nullable()->constrained('lineas')->onDelete('set null');
            $table->foreignId('proveedor_id')->index()->constrained('proveedores')->onDelete('cascade');
            $table->foreignId('unidad_medida_id')->nullable()->constrained('unidad_medidas')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
}
