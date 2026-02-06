<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql5')->table('pago_solicitud_pago', function (Blueprint $table) {
            $table->decimal('saldo_inicial', 15, 2)
                ->after('monto_aplicado')
                ->comment('Saldo inicial de la SPP al momento de aplicar el pago');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql5')->table('pago_solicitud_pago', function (Blueprint $table) {
            $table->dropColumn('saldo_inicial');
        });
    }
};
