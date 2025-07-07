<?php

use App\Enums\EstadoUsuario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->enum('tipo_relacion', ['PRINCIPAL', 'SECUNDARIO'])->default('SECUNDARIO');
            $table->boolean('activo')->default(true);
            $table->enum('estado', EstadoUsuario::values())->default(EstadoUsuario::Registrado->value);;
            $table->timestamp('fecha_asignacion')->useCurrent();
            $table->timestamp('fecha_desasignacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Un usuario puede estar en múltiples proveedores, pero solo una relación activa por proveedor
            $table->unique(['user_id', 'proveedor_id']);
            $table->index(['proveedor_id', 'activo']);
            $table->index(['user_id', 'tipo_relacion']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_proveedor');
    }
};
