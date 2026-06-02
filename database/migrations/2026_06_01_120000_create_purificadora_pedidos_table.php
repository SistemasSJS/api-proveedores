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
        Schema::create('purificadora_pedidos', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 120);
            $table->string('celular', 10);
            $table->string('correo', 180)->nullable();
            $table->string('calle', 120);
            $table->string('numero', 20);
            $table->string('colonia', 120);
            $table->char('codigo_postal', 5)->nullable();
            $table->string('municipio', 120)->default('Ahome');

            $table->timestamps();

            $table->index('celular');
            $table->index('codigo_postal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purificadora_pedidos');
    }
};
