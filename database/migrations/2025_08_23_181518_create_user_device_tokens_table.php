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
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token', 255)->unique();
            $table->enum('platform', ['ios', 'android', 'web'])->default('android');
            $table->string('device_id', 100)->nullable();
            $table->string('device_name', 100)->nullable();
            $table->json('metadata')->nullable(); // Para guardar versión, modelo, etc.
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['user_id', 'is_active']);
            $table->index(['platform', 'is_active']);
            $table->unique(['user_id', 'device_id'], 'user_device_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_device_tokens');
    }
};
