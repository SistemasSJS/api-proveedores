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
        Schema::create('oc_construcc', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->string('orden_compra_id')->nullable();
            $table->string('estatus')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['proveedor_id', 'estatus']);
            $table->index(['empresa_id', 'orden_compra_id']);
            $table->index('estatus');

            // Claves foráneas
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oc_construcc');
    }
};
