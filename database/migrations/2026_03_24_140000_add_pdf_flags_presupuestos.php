<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alinea el PDF con los toggles del front: IVA opcional en términos;
     * traslados/viáticos null = sección desactivada (no mostrar párrafo).
     */
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->boolean('term_cond_impuestos_en_pdf')->default(true)->after('term_cond_moneda');
        });

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->boolean('obs_traslados')->nullable()->change();
            $table->boolean('obs_viaticos')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropColumn('term_cond_impuestos_en_pdf');
        });

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->boolean('obs_traslados')->default(false)->nullable(false)->change();
            $table->boolean('obs_viaticos')->default(false)->nullable(false)->change();
        });
    }
};
