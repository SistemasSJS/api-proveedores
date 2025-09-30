<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cuentas_bancarias', function (Blueprint $table) {
            $table->id();

            // Relación con proveedor
            $table->foreignId('proveedor_id')->nullable()->index()->constrained('proveedores')->restrictOnDelete();
            // Datos de la cuenta
            $table->string('alias', 50);
            $table->string('banco_clave', 10);
            $table->string('banco_nombre', 50);
            // Tipo de cuenta
            $table->enum('tipo_cuenta', ['clabe', 'tarjeta', 'cuenta']);
            $table->string('campo_dependiente', 20);
            // Titular y referencias
            $table->string('titular_cuenta', 100);
            $table->string('referencia', 50)->nullable();
            $table->string('sucursal')->nullable();
            $table->string('swift')->nullable();
            // Preferida
            $table->boolean('preferida')->default(false);
            // Nueva columna: moneda
            $table->string('moneda', 10)->default('MXN');
            // Timestamps
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_bancarias');
    }
};
