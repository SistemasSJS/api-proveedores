<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('proveedores', function (Blueprint $table) {
            // Asegurarnos que los campos fiscales sean consistentes con el frontend
            $table->string('calle')->nullable()->after('direccion_fiscal');
            $table->string('numero_exterior')->nullable()->after('calle');
            $table->string('numero_interior')->nullable()->after('numero_exterior');
            $table->string('colonia')->nullable()->after('numero_interior');
            $table->string('ciudad')->nullable()->change(); // Ya existe pero aseguramos que sea nullable
            $table->string('estado')->nullable()->change(); // Ya existe pero aseguramos que sea nullable
            $table->string('codigo_postal')->nullable()->change(); // Ya existe pero aseguramos que sea nullable
            $table->string('pais')->nullable()->default('México')->after('codigo_postal');
            
            // Campos de régimen fiscal
            $table->string('regimen_fiscal_clave')->nullable()->change();
            $table->string('regimen_fiscal_nombre')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn([
                'calle',
                'numero_exterior',
                'numero_interior',
                'colonia',
                'pais'
            ]);
            
            // Revertir los cambios de nullable
            $table->string('ciudad')->change();
            $table->string('estado')->change();
            $table->string('codigo_postal')->change();
            $table->string('regimen_fiscal_clave')->change();
            $table->string('regimen_fiscal_nombre')->change();
        });
    }
};
