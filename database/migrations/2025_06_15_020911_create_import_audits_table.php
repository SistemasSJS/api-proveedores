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
        Schema::create('import_audits', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique();
            $table->foreignId('proveedor_id')->index()->constrained('proveedores')->restrictOnDelete();
            $table->enum('tipo', ['productos', 'marcas', 'lineas', 'categorias'])->default('productos');
            $table->string('archivo');
            $table->enum('estado', ['pendiente', 'procesando', 'preview', 'confirmado', 'completado', 'error']);
            $table->integer('total_registros')->default(0);
            $table->integer('nuevos')->default(0);
            $table->integer('actualizados')->default(0);
            $table->integer('eliminados')->default(0);
            $table->integer('errores')->default(0);
            $table->json('preview_data')->nullable();
            $table->json('errores_detalle')->nullable();
            $table->integer('progreso')->default(0);
            $table->timestamp('inicio_proceso')->nullable();
            $table->timestamp('fin_proceso')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_audits');
    }
};
