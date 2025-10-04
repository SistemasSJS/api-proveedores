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
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            // Agregar campo único de cotización (puede ser PDF, imagen, etc.)
            $table->string('ruta_archivo_cotizacion', 500)->nullable()->after('ruta_archivo_factura_xml');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn('ruta_archivo_cotizacion');
        });
    }
};
