<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\EstadoSP;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_pago', function (Blueprint $table) {
            $table->id();
            $table->string('numero_folio_solicitud')->unique();
            $table->text('descripcion_concepto');
            $table->string('ruta_archivo_factura_xml');
            $table->string('ruta_archivo_factura_pdf');
            $table->enum('estado_solicitud', EstadoSP::values())->default(EstadoSP::PENDIENTE->value);
            $table->string('ruta_archivo_comprobante_pago')->nullable();

            // Relaciones
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();

            // Fechas clave
            $table->timestamp('fecha_registro_pendiente')->useCurrent();
            $table->timestamp('fecha_aprobado')->nullable();
            $table->timestamp('fecha_rechazado')->nullable();
            $table->timestamp('fecha_confirmacion_pago')->nullable();

            // Motivos
            $table->string('motivo_rechazo', 500)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_pago');
    }
};
