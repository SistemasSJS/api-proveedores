<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // 1. Eliminar precios que ya no usas
            $table->dropColumn([
                'precio_de_lista',
                'precio_publico',
                'precio_con_IVA',
                'precio_sin_IVA',
                'precio_promocional',
                'precio_distribuidor',
                'precio_especial',
            ]);


            // 3. Agregar precio_menudeo
            $table->decimal('precio_menudeo', 10, 2)->nullable()->after('precio_mayoreo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Restaurar columnas eliminadas
            $table->decimal('precio_de_lista', 10, 2)->nullable();
            $table->decimal('precio_publico', 10, 2)->nullable();
            $table->decimal('precio_con_IVA', 10, 2)->nullable();
            $table->decimal('precio_sin_IVA', 10, 2)->nullable();
            $table->decimal('precio_promocional', 10, 2)->nullable();
            $table->decimal('precio_distribuidor', 10, 2)->nullable();
            $table->decimal('precio_especial', 10, 2)->nullable();
            // Eliminar precio_menudeo
            $table->dropColumn('precio_menudeo');
        });
    }
};
