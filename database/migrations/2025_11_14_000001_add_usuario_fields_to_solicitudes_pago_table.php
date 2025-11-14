<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            // Datos del usuario de Construcc que genera la SP
            $table->unsignedBigInteger('usuario_id')->nullable()->after('empresa_construcc_id');
            $table->string('usuario_nombre', 255)->nullable()->after('usuario_id');

            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropIndex(['usuario_id']);
            $table->dropColumn(['usuario_id', 'usuario_nombre']);
        });
    }
};
