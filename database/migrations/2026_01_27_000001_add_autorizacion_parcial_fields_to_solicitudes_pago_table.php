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
            // Campos para autorización parcial (monto_autorizado ya existe)
            $table->unsignedBigInteger('usuario_autorizo_parcial_id')->nullable()->after('monto_autorizado')->comment('ID del usuario que autorizó parcialmente');
            $table->string('usuario_autorizo_parcial_nombre')->nullable()->after('usuario_autorizo_parcial_id')->comment('Nombre del usuario que autorizó parcialmente');
            $table->text('motivo_autorizacion_parcial')->nullable()->after('usuario_autorizo_parcial_nombre')->comment('Motivo/Notas de la autorización parcial');
            $table->timestamp('fecha_autorizacion_parcial')->nullable()->after('motivo_autorizacion_parcial')->comment('Fecha de autorización parcial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn([
                'usuario_autorizo_parcial_id',
                'usuario_autorizo_parcial_nombre',
                'motivo_autorizacion_parcial',
                'fecha_autorizacion_parcial'
            ]);
        });
    }
};
