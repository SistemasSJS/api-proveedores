<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductosTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            $table->string('nombre');
            $table->text('descripcion')->nullable();

            $table->string('codigo_interno')->nullable();
            $table->float('precio_unitario')->default(0);
            $table->boolean('disponible')->default(true);

            // Relaciones
            $table->foreignId('proveedor_id')->index()->constrained('proveedores')->onDelete('cascade');
            $table->foreignId('unidad_medida_id')->nullable()->constrained('unidad_medidas')->onDelete('set null');
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
}
