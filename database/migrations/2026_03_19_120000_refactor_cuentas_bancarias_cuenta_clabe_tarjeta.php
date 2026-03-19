<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ========== Tabla cuentas_bancarias ==========
        Schema::table('cuentas_bancarias', function (Blueprint $table) {
            $table->string('cuenta', 20)->nullable()->after('banco_nombre');
            $table->string('clabe', 20)->nullable()->after('cuenta');
            $table->string('tarjeta', 20)->nullable()->after('clabe');
        });

        // Migrar datos: campo_dependiente -> cuenta/clabe/tarjeta según tipo_cuenta
        DB::table('cuentas_bancarias')->where('tipo_cuenta', 'cuenta')->update([
            'cuenta' => DB::raw('campo_dependiente'),
        ]);
        DB::table('cuentas_bancarias')->where('tipo_cuenta', 'clabe')->update([
            'clabe' => DB::raw('campo_dependiente'),
        ]);
        DB::table('cuentas_bancarias')->where('tipo_cuenta', 'tarjeta')->update([
            'tarjeta' => DB::raw('campo_dependiente'),
        ]);

        Schema::table('cuentas_bancarias', function (Blueprint $table) {
            $table->dropColumn(['tipo_cuenta', 'campo_dependiente']);
        });

        // ========== Tabla solicitud_pago_cuentas_bancarias ==========
        Schema::table('solicitud_pago_cuentas_bancarias', function (Blueprint $table) {
            $table->string('cuenta', 20)->nullable()->after('banco_nombre');
            $table->string('clabe', 20)->nullable()->after('cuenta');
            $table->string('tarjeta', 20)->nullable()->after('clabe');
        });

        DB::table('solicitud_pago_cuentas_bancarias')->where('tipo_cuenta', 'cuenta')->update([
            'cuenta' => DB::raw('campo_dependiente'),
        ]);
        DB::table('solicitud_pago_cuentas_bancarias')->where('tipo_cuenta', 'clabe')->update([
            'clabe' => DB::raw('campo_dependiente'),
        ]);
        DB::table('solicitud_pago_cuentas_bancarias')->where('tipo_cuenta', 'tarjeta')->update([
            'tarjeta' => DB::raw('campo_dependiente'),
        ]);

        Schema::table('solicitud_pago_cuentas_bancarias', function (Blueprint $table) {
            $table->dropColumn(['tipo_cuenta', 'campo_dependiente']);
        });
    }

    public function down(): void
    {
        // ========== Revertir solicitud_pago_cuentas_bancarias ==========
        Schema::table('solicitud_pago_cuentas_bancarias', function (Blueprint $table) {
            $table->string('tipo_cuenta')->after('banco_nombre');
            $table->string('campo_dependiente')->after('tipo_cuenta');
        });

        foreach (DB::table('solicitud_pago_cuentas_bancarias')->get() as $row) {
            $tipo = $row->cuenta ? 'cuenta' : ($row->clabe ? 'clabe' : 'tarjeta');
            $valor = $row->cuenta ?? $row->clabe ?? $row->tarjeta ?? '';
            DB::table('solicitud_pago_cuentas_bancarias')->where('id', $row->id)->update([
                'tipo_cuenta' => $tipo,
                'campo_dependiente' => $valor,
            ]);
        }

        Schema::table('solicitud_pago_cuentas_bancarias', function (Blueprint $table) {
            $table->dropColumn(['cuenta', 'clabe', 'tarjeta']);
        });

        // ========== Revertir cuentas_bancarias ==========
        Schema::table('cuentas_bancarias', function (Blueprint $table) {
            $table->enum('tipo_cuenta', ['clabe', 'tarjeta', 'cuenta'])->after('banco_nombre');
            $table->string('campo_dependiente', 20)->after('tipo_cuenta');
        });

        foreach (DB::table('cuentas_bancarias')->get() as $row) {
            $tipo = $row->cuenta ? 'cuenta' : ($row->clabe ? 'clabe' : 'tarjeta');
            $valor = $row->cuenta ?? $row->clabe ?? $row->tarjeta ?? '';
            DB::table('cuentas_bancarias')->where('id', $row->id)->update([
                'tipo_cuenta' => $tipo,
                'campo_dependiente' => $valor,
            ]);
        }

        Schema::table('cuentas_bancarias', function (Blueprint $table) {
            $table->dropColumn(['cuenta', 'clabe', 'tarjeta']);
        });
    }
};
