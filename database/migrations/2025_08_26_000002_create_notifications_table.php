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
        Schema::create('notifications', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->string('type');

            // Relación con el tipo de notificación
            $table->foreignId('tipo_notificacion_id')
                ->nullable()
                ->constrained('tipos_notificacion')
                ->cascadeOnDelete();

            // Relación polimórfica con el notifiable (usuarios u otros modelos)
            $table->morphs('notifiable');

            // Datos específicos de la notificación en JSON
            $table->json('data');

            // Fecha de lectura
            $table->timestamp('read_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index('tipo_notificacion_id');
            $table->index('read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
