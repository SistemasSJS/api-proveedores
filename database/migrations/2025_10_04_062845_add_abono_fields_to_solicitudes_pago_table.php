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
            // Campos para manejo de pagos parciales
            $table->decimal('monto_abonado', 12, 2)->default(0)->after('monto_total');
            $table->decimal('saldo_pendiente', 12, 2)->default(0)->after('monto_abonado');
            $table->boolean('pago_completo')->default(false)->after('saldo_pendiente');
            $table->text('notas_abono')->nullable()->after('pago_completo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn([
                'monto_abonado', 
                'saldo_pendiente', 
                'pago_completo', 
                'notas_abono'
            ]);
        });
    }
};
