<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('productos', function (Blueprint $table) {
            // Si no existe todavía, agregamos un índice único
            $table->unique(['codigo_interno', 'proveedor_id'], 'productos_codigo_proveedor_unique');
        });
    }

    public function down()
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_codigo_proveedor_unique');
        });
    }
};
