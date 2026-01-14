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
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('tipo_alta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        // eliminar la columna tipo_alta    
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn('tipo_alta');
        });
    }
};
