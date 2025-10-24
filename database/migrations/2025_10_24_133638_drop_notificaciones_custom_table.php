<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Elimina la tabla 'notificaciones' custom porque ahora usamos
     * el sistema estándar de Laravel Notifications con la tabla 'notifications'
     */
    public function up(): void
    {
        Schema::dropIfExists('notificaciones');
    }

    /**
     * Reverse the migrations.
     * 
     * Recrea la tabla por si necesitamos hacer rollback
     */
    public function down(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50);
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->string('titulo', 255);
            $table->text('mensaje');
            $table->json('data')->nullable();
            $table->boolean('leida')->default(false);
            $table->timestamps();
            
            $table->index(['proveedor_id', 'leida', 'created_at']);
            $table->index('tipo');
        });
    }
};
