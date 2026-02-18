<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1️⃣ Convertir NULL a false
        DB::table('solicitudes_pago')
            ->whereNull('tiene_factura')
            ->update(['tiene_factura' => false]);

        // 2️⃣ Modificar la columna: default false y no nullable
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->boolean('tiene_factura')
                ->default(false)
                ->nullable(false)
                ->change();
        });
    }

    public function down(): void
    {
        // Volver a nullable y default null
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->boolean('tiene_factura')
                ->nullable()
                ->default(null)
                ->change();
        });

        // Opcional: regresar false a null (solo si quieres dejarlo exactamente como antes)
        DB::table('solicitudes_pago')
            ->where('tiene_factura', false)
            ->update(['tiene_factura' => null]);
    }
};
