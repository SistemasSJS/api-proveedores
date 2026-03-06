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
        Schema::create('cartera_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('puesto')->nullable();
            $table->string('empresa');
            $table->string('telefono')->nullable();
            $table->string('correo')->nullable();
            $table->timestamps();

            $table->index('proveedor_id');
            $table->index(['proveedor_id', 'empresa']);
            $table->index(['proveedor_id', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartera_clientes');
    }
};
