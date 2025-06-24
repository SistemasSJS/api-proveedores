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
            $table->string('sku', 100)->unique();
            $table->string('modelo', 60)->nullable();
            $table->string('codigo_interno', 60)->nullable();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('logo')->nullable();
            $table->string('imagen_principal', 500)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Stocks
            $table->integer('stock')->default(0);

            // precios
            $table->decimal('precio_base', 10, 2)->nullable();
            $table->decimal('precio_de_lista', 10, 2)->nullable();
            $table->decimal('precio_publico', 10, 2)->nullable();
            $table->decimal('precio_mayoreo', 10, 2)->nullable();
            $table->decimal('precio_con_IVA', 10, 2)->nullable();
            $table->decimal('precio_sin_IVA', 10, 2)->nullable();
            $table->decimal('precio_promocional', 10, 2)->nullable();
            $table->decimal('precio_distribuidor', 10, 2)->nullable();
            $table->decimal('precio_especial', 10, 2)->nullable();

            // FK
            $table->foreignId('proveedor_id')->index()->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('marca_id')->nullable()->constrained('marcas')->nullOnDelete();
            $table->foreignId('linea_id')->nullable()->constrained('lineas')->nullOnDelete();
            $table->foreignId('unidad_medida_id')->nullable()->constrained('unidad_medidas')->nullOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');

            $table->unique(['sku', 'proveedor_id'], 'uk_sku_proveedor');
            $table->index(['proveedor_id', 'categoria_id'], 'idx_proveedor_categoria');
            $table->index(['marca_id', 'linea_id'], 'idx_marca_linea');
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
