<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla para almacenar los pagos realizados a proveedores.
     * Un pago puede aplicar a múltiples solicitudes de pago (SPP).
     */
    public function up(): void
    {
        Schema::connection('mysql5')->create('pagos_spp', function (Blueprint $table) {
            $table->id();
            
            // Comprobante de pago (único para cada pago registrado)
            $table->string('comprobante_pago', 500)->unique()->comment('Ruta del archivo del comprobante de pago');
            
            // Fechas
            $table->timestamp('fecha_pago')->comment('Fecha en que se realizó el pago');
            $table->timestamp('fecha_registro')->useCurrent()->comment('Fecha de registro del pago en el sistema');
            
            // Referencia de pago
            $table->string('referencia_pago', 100)->comment('Referencia o número de transacción del pago');
            
            // Datos bancarios del pago
            $table->string('banco_pago', 100)->nullable()->comment('Banco desde donde se realizó el pago');
            $table->string('cuenta_origen', 50)->nullable()->comment('Número de cuenta origen del pago');
            $table->string('tipo_cuenta_origen', 50)->nullable()->comment('Tipo de cuenta origen');
            $table->string('clabe_interbancaria_origen', 18)->nullable()->comment('CLABE de la cuenta origen');
            
            // Datos bancarios del proveedor (cuenta destino)
            $table->string('banco_destino', 100)->nullable()->comment('Banco del proveedor que recibe el pago');
            $table->string('cuenta_destino', 50)->nullable()->comment('Número de cuenta destino');
            $table->string('tipo_cuenta_destino', 50)->nullable()->comment('Tipo de cuenta destino');
            $table->string('clabe_interbancaria_destino', 18)->nullable()->comment('CLABE de la cuenta destino');
            $table->string('titular_cuenta_destino', 255)->nullable()->comment('Titular de la cuenta destino');
            
            // Montos
            $table->decimal('monto_total', 15, 2)->comment('Monto total del pago');
            
            // Metadatos
            $table->text('observaciones')->nullable()->comment('Observaciones adicionales sobre el pago');
            $table->foreignId('usuario_registro_id')->nullable()->comment('ID del usuario que registró el pago');
            $table->string('usuario_registro_nombre', 255)->nullable()->comment('Nombre del usuario que registró el pago');
            
            // Relación con empresa constructora
            $table->foreignId('empresa_construcc_id')->nullable()->comment('Empresa constructora que realiza el pago');
            
            $table->timestamps();
            
            // Índices para búsquedas frecuentes
            $table->index('fecha_pago');
            $table->index('fecha_registro');
            $table->index('referencia_pago');
            $table->index('empresa_construcc_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql5')->dropIfExists('pagos_spp');
    }
};
