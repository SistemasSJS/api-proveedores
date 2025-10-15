<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->timestamp('fecha_rechazo')->nullable()->after('fecha_rechazado');
            $table->timestamp('fecha_pago')->nullable()->after('fecha_confirmacion_pago');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn(['fecha_rechazo', 'fecha_pago']);
        });
    }
};