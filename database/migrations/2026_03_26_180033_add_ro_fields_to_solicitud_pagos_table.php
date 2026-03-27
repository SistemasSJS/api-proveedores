<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->decimal('validacion_monto', 12, 2)->nullable()->after('monto_autorizado');
            $table->unsignedBigInteger('validacion_usuario_id')->nullable()->after('validacion_monto');
            $table->timestamp('validacion_fecha')->nullable()->after('validacion_usuario_id');
            $table->text('validacion_motivo')->nullable()->after('validacion_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn([
                'validacion_monto',
                'validacion_usuario_id',
                'validacion_fecha',
                'validacion_motivo',
            ]);
        });
    }
};
