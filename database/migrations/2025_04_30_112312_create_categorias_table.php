<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('categoria_padre_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->timestamps();
            $table->enum('estatus', ['activo', 'inactivo'])->default('activo');
            $table->unique(['nombre', 'proveedor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
