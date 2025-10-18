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
            $table->string('referencia_oc')->nullable()->after('numero_folio_solicitud');
            $table->boolean('origen_oc')->default(false)->after('referencia_oc');
            $table->decimal('monto_oc_original', 12, 2)->nullable()->after('origen_oc');
            
            // Índice para búsquedas por referencia de OC
            $table->index('referencia_oc');
            $table->index('origen_oc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropIndex(['referencia_oc']);
            $table->dropIndex(['origen_oc']);
            $table->dropColumn(['referencia_oc', 'origen_oc', 'monto_oc_original']);
        });
    }
};
