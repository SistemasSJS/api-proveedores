<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->boolean('is_proveedor_sp')->default(false)->after('telefono');
            $table->boolean('is_proveedor_catalogo')->default(false)->after('is_proveedor_sp');
            $table->boolean('cambiar_pass_default')->default(true)->after('is_proveedor_catalogo');
            $table->boolean('perfil_empresa_completo')->default(false)->after('cambiar_pass_default');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn([
                'is_proveedor_sp',
                'is_proveedor_catalogo',
                'cambiar_pass_default',
                'perfil_empresa_completo'
            ]);
        });
    }
};
