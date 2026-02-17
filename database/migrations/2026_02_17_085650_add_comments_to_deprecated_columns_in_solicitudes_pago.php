<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agregar comentarios a columnas deprecadas para evitar confusión
     */
    public function up(): void
    {
        // Agregar comentarios usando SQL directo (Laravel no soporta modificar solo comentarios)
        DB::connection('mysql5')->statement("
            ALTER TABLE solicitudes_pago 
            MODIFY COLUMN monto_abonado DECIMAL(15,2) DEFAULT 0 
            COMMENT 'DEPRECATED: Usar calcularMontoAbonado() del modelo. Se mantiene por compatibilidad.'
        ");

        DB::connection('mysql5')->statement("
            ALTER TABLE solicitudes_pago 
            MODIFY COLUMN saldo_pendiente DECIMAL(15,2) DEFAULT 0 
            COMMENT 'DEPRECATED: Usar calcularSaldoRestante() del modelo. Se mantiene por compatibilidad.'
        ");

        DB::connection('mysql5')->statement("
            ALTER TABLE solicitudes_pago 
            MODIFY COLUMN pago_completo TINYINT(1) DEFAULT 0 
            COMMENT 'DEPRECATED: Usar estaPagadaCompletamente() del modelo. Se mantiene por compatibilidad.'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover comentarios
        DB::connection('mysql5')->statement("
            ALTER TABLE solicitudes_pago 
            MODIFY COLUMN monto_abonado DECIMAL(15,2) DEFAULT 0
        ");

        DB::connection('mysql5')->statement("
            ALTER TABLE solicitudes_pago 
            MODIFY COLUMN saldo_pendiente DECIMAL(15,2) DEFAULT 0
        ");

        DB::connection('mysql5')->statement("
            ALTER TABLE solicitudes_pago 
            MODIFY COLUMN pago_completo TINYINT(1) DEFAULT 0
        ");
    }
};
