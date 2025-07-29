<?php

use App\Enums\EstadoUsuario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social')->unique();
            $table->string('rfc')->unique()->nullable();
            $table->string('logo')->nullable();
            $table->string('tipo_persona')->nullable();
            $table->string('direccion_fiscal')->nullable();
            $table->string('estado')->nullable();
            $table->string('municipio')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->enum('estatus', EstadoUsuario::values())
                ->default(EstadoUsuario::REGISTRADO->value);
            $table->text('notas')->nullable();
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            // $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Relación con Users
            $table->timestamps();

            // requirede fields
            $table->string('nombre_propietario')->nullable();
            $table->string('nombre_de_quien_registra')->nullable();
            $table->string('nombre_comercial')->nullable();
            $table->foreignId('tipos_empresa_id')->nullable()->constrained('tipos_empresa')->onDelete('cascade');
            $table->string('tipos_empresa_otro')->nullable()->nullable();
            $table->string('descripcion_giro_empresa')->nullable();
            $table->string('direccion_empresa')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('pagina_web')->nullable();
            // $table->point('ubicacion_empresa')->nullable();
            // cONTACTO SERA TABLA RELAICONAL????
            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_cargo')->nullable();
            $table->string('contacto_telefono')->nullable();
            $table->string('contacto_correo')->nullable();

            // Crear índices en los campos más importantes para búsquedas rápidas
            $table->index('nombre_comercial');
            $table->index('razon_social');
            $table->index('rfc');
            $table->index('email');
            $table->index('estado');
            $table->index('municipio');
            $table->index('codigo_postal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
