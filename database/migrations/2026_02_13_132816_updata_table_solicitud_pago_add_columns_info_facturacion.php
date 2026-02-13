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
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {

            // Regimen Fiscal (string)
            if (!Schema::connection('mysql5')->hasColumn('solicitudes_pago', 'rf')) {
                $table->string('rf')->nullable()->after('fp')->comment('Régimen Fiscal');
            }

            // Razón social (ID numérico) después de datos_facturacion_id
            if (!Schema::connection('mysql5')->hasColumn('solicitudes_pago', 'razon_social_id')) {
                $table->unsignedBigInteger('razon_social_id')
                      ->nullable()
                      ->after('datos_facturacion_id')
                      ->comment('ID de la razón social / datos fiscales');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {

            if (Schema::connection('mysql5')->hasColumn('solicitudes_pago', 'rf')) {
                $table->dropColumn('rf');
            }

            if (Schema::connection('mysql5')->hasColumn('solicitudes_pago', 'razon_social_id')) {
                $table->dropColumn('razon_social_id');
            }
        });
    }
};
