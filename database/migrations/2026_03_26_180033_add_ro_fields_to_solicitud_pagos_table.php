<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->decimal('ro_monto', 12, 2)->nullable()->after('monto_autorizado');
            $table->unsignedBigInteger('ro_usuario_id')->nullable()->after('ro_monto');
            $table->timestamp('ro_validacion_fecha')->nullable()->after('ro_usuario_id');
            $table->text('ro_motivo')->nullable()->after('ro_validacion_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn([
                'ro_monto',
                'ro_usuario_id',
                'ro_validacion_fecha',
                'ro_motivo',
            ]);
        });
    }
};
