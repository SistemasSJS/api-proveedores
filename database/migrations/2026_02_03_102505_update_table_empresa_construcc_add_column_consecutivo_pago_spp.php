<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_construcc', function (Blueprint $table) {
            $table->unsignedInteger('consecutivo_pago_spp')
                ->default(1)
                ->after('consecutivo_sp')
                ->comment('consecutivo_pago_spp por empresa constructora');
        });
    }

    public function down(): void
    {
        Schema::table('empresa_construcc', function (Blueprint $table) {
            $table->dropColumn('consecutivo_pago_spp');
        });
    }
};
