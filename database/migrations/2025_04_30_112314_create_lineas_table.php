<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->string('nombre');
            $table->enum('estatus', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->unique(['nombre', 'proveedor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineas');
    }
};
