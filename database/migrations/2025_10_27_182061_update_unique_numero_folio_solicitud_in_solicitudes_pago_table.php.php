<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            // Eliminar índice único anterior
            $table->dropUnique(['numero_folio_solicitud']);

            // Crear índice único combinado (numero_folio_solicitud + proveedor_id)
            $table->unique(['numero_folio_solicitud', 'proveedor_id'], 'unique_folio_proveedor');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            // Eliminar el índice compuesto
            $table->dropUnique('unique_folio_proveedor');

            // Restaurar la restricción única original
            $table->unique('numero_folio_solicitud');
        });
    }
};
