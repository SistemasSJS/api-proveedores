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
        Schema::table('orden_compra_solicitud_pago', function (Blueprint $table) {
            // Modificar columnas para que sean nullable y tengan valor por defecto NULL
            $table->unsignedBigInteger('orden_compra_id')->nullable()->default(NULL)->change();
            $table->unsignedBigInteger('solicitud_pago_id')->nullable()->default(NULL)->change();
            $table->decimal('monto_asociado', 12, 2)->nullable()->default(NULL)->change();
            $table->datetime('fecha_vinculacion')->nullable()->default(NULL)->change();
            $table->text('notas')->nullable()->default(NULL)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_compra_solicitud_pago', function (Blueprint $table) {
            // Revertir cambios a no nullable (original)
            $table->unsignedBigInteger('orden_compra_id')->nullable(false)->default(NULL)->change();
            $table->unsignedBigInteger('solicitud_pago_id')->nullable(false)->default(NULL)->change();
            $table->decimal('monto_asociado', 12, 2)->nullable(false)->default(0)->change();
            $table->datetime('fecha_vinculacion')->nullable(false)->change();
            $table->text('notas')->nullable()->default(NULL)->change();
        });
    }
};
