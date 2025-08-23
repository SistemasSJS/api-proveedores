<?php

use App\Enums\EstadoProceso;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->timestamp('fecha_cotizacion');
            $table->date('fecha_vencimiento');
            $table->decimal('total', 12, 2);
            $table->text('observaciones')->nullable();
            $table->enum('estatus', EstadoProceso::values())->default(EstadoProceso::PENDIENTE->value);
            $table->timestamps();

            $table->index(['fecha_vencimiento']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cotizaciones');
    }
};
