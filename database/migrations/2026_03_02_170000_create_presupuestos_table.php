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
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_presupuesto');
            $table->date('fecha_emision');
            $table->text('concepto_general');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->boolean('con_iva')->default(true);
            $table->decimal('iva_porcentaje', 5, 2)->default(16.00);
            $table->decimal('iva_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->json('empresa_emisora_datos')->nullable();
            $table->json('empresa_receptora_datos')->nullable();
            $table->json('condiciones')->nullable();
            $table->text('observaciones')->nullable();

            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->cascadeOnDelete();

            $table->foreignId('empresa_receptora_id')
                ->nullable()
                ->constrained('proveedores')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['proveedor_id', 'numero_presupuesto'], 'presupuestos_proveedor_numero_unique');
            $table->index('proveedor_id');
            $table->index('user_id');
            $table->index('fecha_emision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
