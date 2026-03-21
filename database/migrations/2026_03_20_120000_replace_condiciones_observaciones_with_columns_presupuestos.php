<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reemplaza condiciones (JSON) y observaciones (TEXT) por columnas explícitas.
     *
     * Términos y condiciones: term_cond_*
     * Observaciones: obs_*
     */
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            // Términos y condiciones
            $table->unsignedSmallInteger('term_cond_dias_vigencia')->nullable()->after('empresa_receptora_correo');
            $table->string('term_cond_moneda', 10)->default('MXN')->after('term_cond_dias_vigencia');
            $table->decimal('term_cond_iva', 5, 2)->default(16)->after('term_cond_moneda');
            $table->decimal('term_cond_anticipo_porcentaje', 5, 2)->nullable()->after('term_cond_iva');
            $table->unsignedSmallInteger('term_cond_tiempo_entrega_dias')->nullable()->after('term_cond_anticipo_porcentaje');

            // Observaciones
            $table->unsignedSmallInteger('obs_garantia_dias')->default(0)->after('term_cond_tiempo_entrega_dias');
            $table->boolean('obs_traslados')->default(false)->after('obs_garantia_dias');
            $table->boolean('obs_viaticos')->default(false)->after('obs_traslados');
            $table->text('motivo_rechazo')->nullable()->after('obs_viaticos');
        });

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropColumn(['condiciones', 'observaciones']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->json('condiciones')->nullable()->after('empresa_receptora_correo');
            $table->text('observaciones')->nullable()->after('condiciones');
        });

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropColumn([
                'term_cond_dias_vigencia',
                'term_cond_moneda',
                'term_cond_iva',
                'term_cond_anticipo_porcentaje',
                'term_cond_tiempo_entrega_dias',
                'obs_garantia_dias',
                'obs_traslados',
                'obs_viaticos',
                'motivo_rechazo',
            ]);
        });
    }
};
