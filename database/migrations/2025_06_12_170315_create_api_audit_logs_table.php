<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla de auditoría de acciones de la API.
 *
 * Archivo: database/migrations/2024_xx_xx_create_api_audit_logs_table.php
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_audit_logs', function (Blueprint $table) {
            $table->id();

            // Identificadores únicos
            $table->uuid('request_id')->unique()->comment('UUID único para cada request');

            // Información del usuario
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID del usuario que realizó la acción');
            $table->string('user_email')->nullable()->comment('Email del usuario');
            $table->string('user_role')->nullable()->comment('Rol del usuario');

            // Información de la acción
            $table->string('action', 50)->comment('Tipo de acción: CREATE, UPDATE, DELETE, etc.');
            $table->string('resource', 100)->nullable()->comment('Recurso afectado: proveedores, productos, etc.');
            $table->unsignedBigInteger('resource_id')->nullable()->comment('ID del recurso afectado');
            $table->unsignedBigInteger('proveedor_context')->nullable()->comment('ID del proveedor en contexto');

            // Información técnica del request
            $table->string('method', 10)->comment('Método HTTP: GET, POST, PUT, DELETE');
            $table->text('path')->comment('Ruta del endpoint');
            $table->text('url')->comment('URL completa del request');
            $table->ipAddress('ip_address')->comment('Dirección IP del cliente');
            $table->text('user_agent')->nullable()->comment('User agent del navegador/cliente');

            // Datos del request y response
            $table->json('request_headers')->nullable()->comment('Headers del request (sanitizado)');
            $table->json('request_body')->nullable()->comment('Body del request (sanitizado)');
            $table->integer('response_status')->comment('Código de estado HTTP de la respuesta');
            $table->json('response_headers')->nullable()->comment('Headers de la respuesta');
            $table->longText('response_body')->nullable()->comment('Body de la respuesta (solo para errores)');

            // Información adicional
            $table->json('error_details')->nullable()->comment('Detalles específicos del error si aplica');
            $table->decimal('processing_time_ms', 8, 2)->nullable()->comment('Tiempo de procesamiento en milisegundos');

            // Timestamps
            $table->timestamp('timestamp')->comment('Timestamp exacto de la acción');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['user_id', 'created_at'], 'idx_user_date');
            $table->index(['action', 'created_at'], 'idx_action_date');
            $table->index(['resource', 'resource_id'], 'idx_resource');
            $table->index(['proveedor_context', 'created_at'], 'idx_proveedor_date');
            $table->index('response_status', 'idx_status');
            $table->index('ip_address', 'idx_ip');
            $table->index('timestamp', 'idx_timestamp');

            // Índice compuesto para consultas complejas
            $table->index(['user_id', 'proveedor_context', 'action', 'created_at'], 'idx_user_proveedor_action_date');

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('proveedor_context')->references('id')->on('proveedors')->onDelete('set null');
        });

        // Crear índice para búsquedas de texto en JSON (MySQL 5.7+)
        // if (config('database.default') === 'mysql') {
        //     DB::statement('CREATE INDEX idx_error_message ON api_audit_logs ((JSON_EXTRACT(error_details, "$.message")))');
        // }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_audit_logs');
    }
};
