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
            $table->string('clave_rastreo')->nullable()->default(null)->after('banco_pago');
            $table->unsignedBigInteger('cuenta_bancaria_empresa_construcc_id')->nullable()->after('clave_rastreo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos_spp', function (Blueprint $table) {
            $table->dropColumn('clave_rastreo');
            $table->dropColumn('cuenta_bancaria_empresa_construcc_id');
        });
    }
};
