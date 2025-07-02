<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('destacado')->default(false)->after('activo');
            $table->string('codigo')->nullable()->after('destacado');
            $table->string('categoria')->nullable()->after('codigo');
            $table->boolean('principal')->default(false)->after('categoria');

            // $table->integer('stock')->default(0)->after('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['destacado', 'codigo', 'categoria', 'stock']);
        });
    }
};
