<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();           // ORDER_CREATED, IMPORT_COMPLETED, etc.
            $table->string('display_name', 100);            // Nombre para mostrar
            $table->string('description', 255)->nullable(); // Descripción del tipo
            $table->string('icon', 50)->nullable();         // Icono para UI (ej: "cart-outline")
            $table->string('color', 20)->default('primary'); // Color para UI
            $table->string('channel', 50)->default('database'); // database, mail, sms, push
            $table->json('template_data')->nullable();      // Plantilla de datos para el tipo
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_types');
    }
};
