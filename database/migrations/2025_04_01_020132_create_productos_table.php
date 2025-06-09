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
            $table->foreignId('catalogo_id')->index()->constrained('catalogos')->onDelete('cascade');
            $table->string('nombre');
            $table->string('logo')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('sku')->nullable();
            $table->string('modelo_codigo')->nullable();
            $table->foreignId('marca_id')->nullable()->constrained('marcas')->onDelete('set null');
            $table->foreignId('linea_id')->nullable()->constrained('lineas')->onDelete('set null');
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
