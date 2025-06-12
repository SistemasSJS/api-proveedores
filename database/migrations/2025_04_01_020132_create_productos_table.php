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
            $table->string('sku', 100)->unique()->after('id');
            $table->foreignId('proveedor_id')->index()->constrained('proveedores')->restrictOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('logo')->nullable();
            $table->foreignId('marca_id')->nullable()->constrained('marcas')->nullOnDelete();
            $table->foreignId('linea_id')->nullable()->constrained('lineas')->nullOnDelete();
            $table->foreignId('unidad_medida_id')->nullable()->constrained('unidad_medidas')->nullOnDelete();
            $table->decimal('precio_base', 10, 2)->nullable()->after('descripcion');
            $table->string('imagen_principal', 500)->nullable()->after('precio_base');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null')->after('proveedor_id');
            $table->boolean('activo')->default(true)->after('linea_id');
            $table->integer('stock')->default(0)->after('activo');
            $table->decimal('peso_kg', 8, 3)->nullable()->after('stock');
            $table->string('dimensiones', 100)->nullable()->after('peso_kg');
            $table->timestamps();
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
