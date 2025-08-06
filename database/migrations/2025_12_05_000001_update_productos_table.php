<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'subcategoria_id')) {
                $table->foreignId('subcategoria_id')
                    ->nullable()
                    ->constrained('categorias') // <- correcto según tu aclaración
                    ->onDelete('set null');
            }

            if (!Schema::hasColumn('productos', 'mostrar_precios')) {
                $table->boolean('mostrar_precios')->default(false);
            }
        });
    }

    public function down()
    {
        Schema::table('productos', function (Blueprint $table) {
            if (Schema::hasColumn('productos', 'subcategoria_id')) {
                $table->dropForeign(['subcategoria_id']);
                $table->dropColumn('subcategoria_id');
            }

            if (Schema::hasColumn('productos', 'mostrar_precios')) {
                $table->dropColumn('mostrar_precios');
            }
        });
    }
};
