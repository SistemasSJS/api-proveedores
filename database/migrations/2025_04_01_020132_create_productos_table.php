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
            // CATALOGO YA NO FORMARA PARTE DE LA APP
            $table->foreignId('proveedor_id')->index()->constrained('proveedores')->restrictOnDelete();
            $table->string('sku')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('logo')->nullable();
            $table->foreignId('marca_id')->nullable()->constrained('marcas')->nullOnDelete();
            $table->foreignId('linea_id')->nullable()->constrained('lineas')->nullOnDelete();
            $table->foreignId('unidad_medida_id')->nullable()->constrained('unidad_medidas')->nullOnDelete();
            // ESPECIFICACIONES:
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
