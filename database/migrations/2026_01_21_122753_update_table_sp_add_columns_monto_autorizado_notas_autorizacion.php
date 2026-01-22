<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table
                ->decimal('monto_autorizado', 10, 2)
                ->nullable()
                ->after('equipo_id');

            $table
                ->text('notas_autorizacion')
                ->nullable()
                ->after('monto_autorizado');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn('monto_autorizado');
            $table->dropColumn('notas_autorizacion');
        });
    }
};
