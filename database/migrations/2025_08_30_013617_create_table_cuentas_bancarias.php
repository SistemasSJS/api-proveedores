<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->index()->constrained('proveedores')->restrictOnDelete();
            $table->string('alias', 50);
            $table->string('banco_clave', 10);
            $table->string('banco_nombre', 50);
            $table->enum('tipo_cuenta', ['clabe', 'tarjeta', 'cuenta']);
            $table->string('campo_dependiente', 20); // CLABE/tarjeta/cuenta
            $table->string('titular_cuenta', 100);
            $table->string('referencia', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_bancarias');
    }
};
