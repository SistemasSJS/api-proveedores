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
        Schema::table('pagos_spp', function (Blueprint $table) {
            $table->string('folio_pago_spp_consecutivo')
                ->nullable()
                ->after('empresa_construcc_id')
            ;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos_spp', function (Blueprint $table) {
            $table->dropColumn('folio_pago_spp_consecutivo');
        });
    }
};
