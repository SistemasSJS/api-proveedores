<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('categorias', function (Blueprint $table) {
            if (!Schema::hasColumn('categorias', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->constrained('categorias');
            }

            if (!Schema::hasColumn('categorias', 'nivel')) {
                $table->integer('nivel')->default(0);
            }

            if (!Schema::hasColumn('categorias', 'proveedor_id')) {
                $table->foreignId('proveedor_id')->constrained('proveedores');
            }
        });
    }

    public function down()
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['proveedor_id']);
            $table->dropColumn(['parent_id', 'nivel', 'proveedor_id']);
        });
    }
};
