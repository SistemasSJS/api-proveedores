<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('import_audits', function (Blueprint $table) {
            $table->integer('marca_imported')->default(0);
            $table->integer('marca_errors')->default(0);
            $table->integer('marca_total')->default(0);

            $table->integer('categoria_imported')->default(0);
            $table->integer('categoria_errors')->default(0);
            $table->integer('categoria_total')->default(0);

            $table->integer('unidad_imported')->default(0);
            $table->integer('unidad_errors')->default(0);
            $table->integer('unidad_total')->default(0);
        });
    }

    public function down()
    {
        Schema::table('import_audits', function (Blueprint $table) {
            $table->dropColumn([
                'marca_imported',
                'marca_errors',
                'marca_total',
                'categoria_imported',
                'categoria_errors',
                'categoria_total',
                'unidad_imported',
                'unidad_errors',
                'unidad_total'
            ]);
        });
    }
};
