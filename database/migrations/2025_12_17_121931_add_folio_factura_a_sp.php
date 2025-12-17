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
            $table->string('folio_factura')->nullable()->after('visto_rechazada');
            $table->json('datos_factura_xml')->nullable()->after('folio_factura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn(['folio_factura', 'datos_factura_xml']);
        });
    }
};
