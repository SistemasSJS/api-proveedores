<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\EstadoSP;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solicitudes_pago', function (Blueprint $table) {
            $table->id();
            // Datos principales
            $table->string('numero_folio_solicitud')->unique();
            $table->text('descripcion_concepto');
            $table->string('ruta_archivo_factura_xml');
            $table->string('ruta_archivo_factura_pdf');
            // Estado
            $table->enum('estado_solicitud', EstadoSP::values())->default(EstadoSP::PENDIENTE->value);
            // Comprobante de pago
            $table->string('ruta_archivo_comprobante_pago')->nullable();
            // Relaciones
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            // Fechas
            $table->timestamp('fecha_registro_pendiente')->useCurrent();
            $table->timestamp('fecha_inicio_procesamiento')->nullable();
            $table->timestamp('fecha_confirmacion_pago')->nullable();
            // Timestamps de Laravel
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_pago');
    }
};
