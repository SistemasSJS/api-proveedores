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
        Schema::table('proveedores', function (Blueprint $table) {
            $table->unsignedInteger('consecutivo_presupuesto_siguiente')
                ->default(1)
                ->after('empresa_construcc_alta')
                ->comment('Consecutivo siguiente para folio de presupuestos por proveedor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn('consecutivo_presupuesto_siguiente');
        });
    }
};

