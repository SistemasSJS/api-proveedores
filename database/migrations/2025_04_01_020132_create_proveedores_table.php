<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('rfc')->unique()->nullable();
            $table->string('tipo_persona')->nullable();
            $table->string('direccion_fiscal')->nullable();
            $table->string('estado')->nullable();
            $table->string('municipio')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->string('estatus')->default('pendiente');
            $table->text('notas')->nullable();
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            // $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Relación con Users
            $table->timestamps();

            // requirede fields
            $table->string('nombre_propietario')->after('id');
            $table->string('nombre_de_quien_registra')->after('nombre_propietario');
            $table->string('nombre_comercial')->after('nombre_de_quien_registra');
            $table->string('razon_social')->after('nombre_comercial');
            $table->foreignId('tipos_empresa_id')->constrained('tipos_empresa')->onDelete('cascade')->after('razon_social');
            $table->string('tipos_empresa_otro')->nullable()->after('tipos_empresa_id');
            $table->string('descripcion_giro_empresa')->after('tipos_empresa_otro');
            $table->string('direccion_empresa')->after('descripcion_giro_empresa');
            $table->string('email')->after('direccion_empresa');
            $table->string('telefono')->after('email');
            $table->string('pagina_web')->after('telefono');
            // $table->point('ubicacion_empresa')->nullable()->after('pagina_web');
            // cONTACTO SERA TABLA RELAICONAL????
            $table->string('contacto_nombre')->after('contacto_correo');
            $table->string('contacto_cargo')->after('contacto_nombre');
            $table->string('contacto_telefono')->after('contacto_cargo');
            $table->string('contacto_correo')->after('contacto_telefonos');

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
