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
        Schema::create('orden_compra_solicitud_pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_compra_id');
            $table->unsignedBigInteger('solicitud_pago_id');
            $table->decimal('monto_asociado', 12, 2);
            $table->datetime('fecha_vinculacion');
            $table->text('notas')->nullable();
            $table->timestamps();

            // Índices con nombres cortos
            $table->index(['orden_compra_id', 'solicitud_pago_id'], 'oc_sp_composite_idx');
            $table->index('orden_compra_id', 'oc_sp_oc_id_idx');
            $table->index('solicitud_pago_id', 'oc_sp_sp_id_idx');

            // Claves foráneas
            $table->foreign('orden_compra_id')->references('id')->on('ordenes_compra')->onDelete('cascade');
            $table->foreign('solicitud_pago_id')->references('id')->on('solicitudes_pago')->onDelete('cascade');

            // Único por combinación
            $table->unique(['orden_compra_id', 'solicitud_pago_id'], 'oc_sp_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_compra_solicitud_pago');
    }
};
