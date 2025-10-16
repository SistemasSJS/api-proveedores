<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('solicitud_pago_cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_pago_id')
                ->constrained('solicitudes_pago')
                ->onDelete('cascade');
            $table->foreignId('cuenta_bancaria_id')
                ->constrained('cuentas_bancarias')
                ->onDelete('cascade');

            // Campos copiados del modelo CuentaBancaria
            $table->string('alias')->nullable();
            $table->string('banco_clave', 10);
            $table->string('banco_nombre');
            $table->string('tipo_cuenta');
            $table->string('campo_dependiente');
            $table->string('titular_cuenta');
            $table->string('referencia');
            $table->tinyInteger('estatus')->default(1);
            $table->string('sucursal')->nullable();
            $table->string('swift')->nullable();
            $table->boolean('preferida')->default(false);

            $table->timestamps();

            // Índices
            $table->index(['solicitud_pago_id', 'cuenta_bancaria_id'], 'sp_cb_index');
            $table->unique(['solicitud_pago_id', 'cuenta_bancaria_id'], 'sp_cb_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('solicitud_pago_cuentas_bancarias');
    }
};
