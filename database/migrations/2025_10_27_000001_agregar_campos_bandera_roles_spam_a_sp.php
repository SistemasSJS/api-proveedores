<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            // Campos booleanos
            $table->boolean('dg')->default(false)->after('perfil_empresa_completo');
            $table->boolean('dt')->default(false)->after('dg');
            $table->boolean('pc')->default(false)->after('dt');
            $table->boolean('si')->default(false)->after('pc');
            $table->boolean('ro')->default(false)->after('si');

            // Fechas de registro asociadas
            $table->timestamp('dg_fecha')->nullable()->after('dg');
            $table->timestamp('dt_fecha')->nullable()->after('dt');
            $table->timestamp('pc_fecha')->nullable()->after('pc');
            $table->timestamp('si_fecha')->nullable()->after('si');
            $table->timestamp('ro_fecha')->nullable()->after('ro');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn([
                'dg',
                'dg_fecha',
                'dt',
                'dt_fecha',
                'pc',
                'pc_fecha',
                'si',
                'si_fecha',
                'ro',
                'ro_fecha',
            ]);
        });
    }
};
