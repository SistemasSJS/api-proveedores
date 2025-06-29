<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up()
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->enum('tipo', ['requisicion_nueva', 'requisicion_actualizada', 'cotizacion_recibida', 'sistema']);
            $table->string('titulo', 200);
            $table->text('mensaje');
            $table->boolean('leida')->default(false);
            $table->timestamp('fecha_lectura')->nullable();
            $table->json('datos')->nullable(); // Para almacenar datos adicionales
            $table->timestamps();
            
            $table->index(['usuario_id', 'leida']);
            $table->index(['tipo']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notificaciones');
    }
};
