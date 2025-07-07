<?php

use App\Enums\EstatusPedido;
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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('requisicion_id')->constrained('requisiciones')->onDelete('cascade');
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->onDelete('cascade');

            // Información del pedido
            $table->string('numero_pedido', 20)->unique();
            $table->datetime('fecha_confirmacion');
            $table->date('fecha_entrega_estimada');
            $table->datetime('fecha_entrega_real')->nullable();

            // Estado del pedido
            $table->enum('estatus', EstatusPedido::values())->default(EstatusPedido::CONFIRMADO->value);

            // Información adicional
            $table->text('observaciones')->nullable();
            $table->text('observaciones_entrega')->nullable();
            $table->string('numero_guia', 50)->nullable();
            $table->string('transportista', 100)->nullable();
            $table->datetime('fecha_cancelacion')->nullable();
            $table->text('motivo_cancelacion')->nullable();

            // Totales
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('impuestos', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Timestamps
            $table->timestamps();

            // Índices
            $table->index(['requisicion_id']);
            $table->index(['cotizacion_id']);
            $table->index(['estatus']);
            $table->index(['fecha_confirmacion']);
            $table->index(['fecha_entrega_estimada']);
            $table->index(['numero_pedido']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
