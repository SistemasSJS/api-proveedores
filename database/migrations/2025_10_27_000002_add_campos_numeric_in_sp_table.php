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
            $table->tinyInteger('dg')->default(0)->after('motivo_rechazo');
            $table->tinyInteger('dt')->default(0)->after('dg');
            $table->tinyInteger('pc')->default(0)->after('dt');
            $table->tinyInteger('si')->default(0)->after('pc');
            $table->tinyInteger('ro')->default(0)->after('si');

            // Fechas asociadas
            $table->timestamp('dg_fecha')->nullable()->after('dg');
            $table->timestamp('dt_fecha')->nullable()->after('dt');
            $table->timestamp('pc_fecha')->nullable()->after('pc');
            $table->timestamp('si_fecha')->nullable()->after('si');
            $table->timestamp('ro_fecha')->nullable()->after('ro');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn([
                'dg',
                'dt',
                'pc',
                'si',
                'ro',
                'dg_fecha',
                'dt_fecha',
                'pc_fecha',
                'si_fecha',
                'ro_fecha',
            ]);
        });
    }
};
