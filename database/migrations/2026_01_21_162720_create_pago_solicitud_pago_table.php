<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla intermedia (pivot) para la relación muchos a muchos entre pagos y solicitudes de pago.
     * Un pago puede aplicar a varias SPP y una SPP puede recibir varios pagos.
     */
    public function up(): void
    {
        Schema::connection('mysql5')->create('pago_solicitud_pago', function (Blueprint $table) {
            $table->id();
            
            // Llaves foráneas para la relación muchos a muchos
            $table->foreignId('pago_spp_id')->constrained('pagos_spp')->onDelete('cascade')->comment('ID del pago');
            $table->foreignId('solicitud_pago_id')->constrained('solicitudes_pago')->onDelete('cascade')->comment('ID de la solicitud de pago');
            
            // Monto aplicado de este pago a esta SPP específica
            $table->decimal('monto_aplicado', 15, 2)->comment('Monto del pago aplicado a esta solicitud de pago');
            
            // Estado del pago en relación a esta SPP específica
            $table->enum('estado_pago', [
                'aplicado',      // Pago aplicado correctamente
                'pendiente',     // Pago registrado pero pendiente de aplicar
                'rechazado',     // Pago rechazado
                'parcial',       // Pago parcial aplicado
                'completado'     // Pago completo de esta SPP
            ])->default('aplicado')->comment('Estado del pago para esta SPP');
            
            // Metadatos de la relación
            $table->text('notas')->nullable()->comment('Notas específicas sobre la aplicación de este pago a esta SPP');
            $table->timestamp('fecha_aplicacion')->useCurrent()->comment('Fecha en que se aplicó el pago a esta SPP');
            
            $table->timestamps();
            
            // Índices y restricciones
            $table->unique(['pago_spp_id', 'solicitud_pago_id'], 'pago_spp_unique');
            $table->index('estado_pago');
            $table->index('fecha_aplicacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql5')->dropIfExists('pago_solicitud_pago');
    }
};
