<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('solicitudes_pago', function (Blueprint $table) {
            $table->id();
            $table->string('numero_folio_solicitud')->unique();
            $table->text('descripcion_concepto');
            $table->string('ruta_archivo_factura_xml');
            $table->string('ruta_archivo_factura_pdf');
            $table->enum('estado_solicitud', ['pendiente', 'procesando', 'pagado']);
            $table->string('ruta_archivo_comprobante_pago')->nullable();
            $table->foreignId('id_proveedor')->constrained('proveedores');
            $table->timestamp('fecha_registro_pendiente');
            $table->timestamp('fecha_inicio_procesamiento')->nullable();
            $table->timestamp('fecha_confirmacion_pago')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('solicitudes_pago');
    }
};
