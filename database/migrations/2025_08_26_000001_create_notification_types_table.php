<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_notificacion', function (Blueprint $table) {
            $table->id();
            // Código interno: COTIZACION, REQUISICION, CHAT_GENERAL
            $table->string('codigo', 50)->unique();

            // Nombre legible para mostrar
            $table->string('nombre', 100);

            // Orden de importancia en la UI
            $table->tinyInteger('orden_importancia')->default(0);

            // Descripción del tipo
            $table->string('descripcion', 255)->nullable();

            // Icono a usar en el menú de notificaciones
            $table->string('icono', 50)->nullable();

            // Color para la UI
            $table->string('color', 20)->default('primary');

            // Canales de emisión: ["database","mail","broadcast"]
            $table->json('canales')->nullable();

            // URL base para detalle del recurso
            $table->string('url_base', 255)->nullable();

            // Activo/Inactivo
            $table->boolean('estatus')->default(true);

            $table->timestamps();

            $table->index('codigo');
            $table->index('estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_notificacion');
    }
};
