<?php

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
        Schema::table('empresa_construcc_proveedor', function (Blueprint $table) {
            $table->unsignedBigInteger('usuario_construcc_id')->nullable()->after('proveedor_id');
            $table->string('usuario_construcc_nombre')->nullable()->after('usuario_construcc_id');
            
            $table->index('usuario_construcc_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_construcc_proveedor', function (Blueprint $table) {
            $table->dropIndex(['usuario_construcc_id']);
            $table->dropColumn(['usuario_construcc_id', 'usuario_construcc_nombre']);
        });
    }
};
