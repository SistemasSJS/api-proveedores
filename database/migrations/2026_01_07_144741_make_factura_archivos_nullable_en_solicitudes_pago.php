<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->string('ruta_archivo_factura_xml')
                ->nullable()
                ->change();

            $table->string('ruta_archivo_factura_pdf')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->string('ruta_archivo_factura_xml')
                ->nullable(false)
                ->change();

            $table->string('ruta_archivo_factura_pdf')
                ->nullable(false)
                ->change();
        });
    }
};
