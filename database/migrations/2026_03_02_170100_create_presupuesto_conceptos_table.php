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
        Schema::create('presupuesto_conceptos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_id')
                ->constrained('presupuestos')
                ->cascadeOnDelete();
            $table->integer('numero');
            $table->text('descripcion');
            $table->decimal('cantidad', 15, 4);
            $table->string('unidad', 50);
            $table->decimal('precio_unitario', 15, 2);
            $table->decimal('precio_total', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['presupuesto_id', 'numero'], 'presupuesto_conceptos_presupuesto_numero_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuesto_conceptos');
    }
};

