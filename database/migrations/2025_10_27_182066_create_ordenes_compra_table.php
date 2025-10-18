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
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->string('numero_orden')->unique();
            $table->date('fecha_orden');
            $table->unsignedBigInteger('proveedor_id');
            $table->unsignedBigInteger('empresa_construcc_id');
            $table->decimal('importe_total', 12, 2);
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada', 'completada', 'parcial'])->default('pendiente');
            $table->datetime('fecha_aprobacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->json('metadata_json')->nullable();
            $table->decimal('monto_sp_asociado', 12, 2)->default(0);
            $table->integer('sp_count')->default(0);
            $table->timestamps();

            // Índices
            $table->index(['proveedor_id', 'estado']);
            $table->index(['empresa_construcc_id', 'fecha_orden']);
            $table->index('estado');
            $table->index('fecha_orden');

            // Claves foráneas
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('cascade');
            $table->foreign('empresa_construcc_id')->references('id')->on('empresa_construcc')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
