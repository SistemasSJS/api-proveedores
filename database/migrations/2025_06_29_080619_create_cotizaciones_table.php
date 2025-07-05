<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisicion_id')->constrained()->nullOnDelete('cascade');
            $table->timestamp('fecha_cotizacion');
            $table->date('fecha_vencimiento');
            $table->decimal('total', 12, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->index(['requisicion_id']);
            $table->index(['fecha_vencimiento']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cotizaciones');
    }
};
