<?php
// database/migrations/xxxx_xx_xx_update_productos_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('productos', function (Blueprint $table) {
            // Asegurar campos obligatorios según especificaciones
            if (!Schema::hasColumn('productos', 'sku')) {
                $table->string('sku')->nullable();
            }

            if (!Schema::hasColumn('productos', 'nombre')) {
                $table->string('nombre');
            }

            if (!Schema::hasColumn('productos', 'descripcion')) {
                $table->text('descripcion')->nullable();
            }

            if (!Schema::hasColumn('productos', 'precio_base')) {
                $table->decimal('precio_base', 10, 2)->nullable();
            }

            if (!Schema::hasColumn('productos', 'imagen_principal')) {
                $table->string('imagen_principal')->nullable();
            }

            // Relaciones obligatorias
            if (!Schema::hasColumn('productos', 'proveedor_id')) {
                $table->foreignId('proveedor_id')->constrained('proveedores');
            }

            if (!Schema::hasColumn('productos', 'categoria_id')) {
                $table->foreignId('categoria_id')->nullable()->constrained('categorias');
            }

            if (!Schema::hasColumn('productos', 'marca_id')) {
                $table->foreignId('marca_id')->nullable()->constrained('marcas');
            }

            if (!Schema::hasColumn('productos', 'linea_id')) {
                $table->foreignId('linea_id')->nullable()->constrained('lineas');
            }

            // Campos adicionales
            if (!Schema::hasColumn('productos', 'activo')) {
                            $table->boolean('activo')->default(true);
            $table->enum('estado', EstadoGeneral::values())->default(EstadoGeneral::Activo->value);;
            }

            if (!Schema::hasColumn('productos', 'stock')) {
                $table->integer('stock')->default(0);
            }
        });
    }

    public function down()
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
            $table->dropForeign(['categoria_id']);
            $table->dropForeign(['marca_id']);
            $table->dropForeign(['linea_id']);
            $table->dropColumn([
                'sku',
                'nombre',
                'descripcion',
                'precio_base',
                'imagen_principal',
                'proveedor_id',
                'categoria_id',
                'marca_id',
                'linea_id',
                'activo',
                'stock',
                'peso_kg',
                'dimensiones'
            ]);
        });
    }
};
