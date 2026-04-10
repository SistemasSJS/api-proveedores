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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();

            // Relación con users
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Proveedor (google, apple)
            $table->string('provider');

            // ID del proveedor (google id, apple sub, etc)
            $table->string('provider_id');

            // Opcional
            $table->string('avatar')->nullable();

            // Opcional (por si después quieres seguridad real)
            $table->text('provider_token')->nullable();
            $table->text('provider_refresh_token')->nullable();

            $table->timestamps();

            // Evitar duplicados
            $table->unique(['provider', 'provider_id']);
            $table->unique(['user_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};