<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('requisiciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->date('fecha_requisicion');
            $table->enum('estatus', ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'COMPLETADA'])->default('PENDIENTE');
            $table->text('observaciones')->nullable();
            $table->decimal('total_estimado', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'fecha_requisicion']);
            $table->index(['proveedor_id', 'estatus']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('requisiciones');
    }
};
