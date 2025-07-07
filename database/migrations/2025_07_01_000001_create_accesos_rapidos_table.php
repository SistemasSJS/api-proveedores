<?php

use App\Enums\EstadoGeneral;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesos_rapidos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('icono');
            $table->string('url');
            $table->string('color')->default('#007bff');
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->enum('estado', EstadoGeneral::values())->default(EstadoGeneral::ACTIVO->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accesos_rapidos');
    }
};
