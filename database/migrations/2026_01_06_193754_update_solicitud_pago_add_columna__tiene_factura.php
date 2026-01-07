<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitud_pago', function (Blueprint $table) {
            $table->boolean('tiene_factura')
                ->nullable()
                ->default(null)
                ->after('cuenta_bancaria_empresa_construcc_id');
        });
    }

    public function down(): void
    {
        Schema::table('solicitud_pago', function (Blueprint $table) {
            $table->dropColumn('tiene_factura');
        });
    }
};
