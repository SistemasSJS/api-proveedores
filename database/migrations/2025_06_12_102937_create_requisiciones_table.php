<?php

use App\Enums\EstadoProceso;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('requisiciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero_requisicion')->unique();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->enum('estatus', EstadoProceso::values())->default(EstadoProceso::PENDIENTE->value);

            $table->date('fecha_requerida');
            $table->timestamp('fecha_cancelacion')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('observaciones_proveedor')->nullable();
            $table->decimal('total_estimado', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['usuario_id', 'estatus']);
            $table->index(['proveedor_id', 'estatus']);
            $table->index(['fecha_requerida']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('requisiciones');
    }
};
