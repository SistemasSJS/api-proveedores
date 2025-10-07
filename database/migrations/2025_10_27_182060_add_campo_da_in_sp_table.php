<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            // Agregar como tinyInteger desde cero
            $table->tinyInteger('da')->default(0)->after('pago_completo');

            // Fechas asociadas
            $table->timestamp('da_fecha')->nullable()->after('da');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn([
                'da',
                'da_fecha',
            ]);
        });
    }
};
