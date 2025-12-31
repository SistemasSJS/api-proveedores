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
            $table->unsignedBigInteger('cuenta_bancaria_empresa_construcc_id')
                ->nullable();

            $table->string('folio_sp_consecutivo')
                ->nullable()
                ->after('cuenta_bancaria_empresa_construcc_id')
            ;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn('cuenta_bancaria_empresa_construcc_id');
            $table->dropColumn('folio_sp_consecutivo');
        });
    }
};
