<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            // Campos de régimen fiscal
            $table->string('regimen_fiscal_clave')->nullable()->after('rfc');
            $table->string('regimen_fiscal_nombre')->nullable()->after('regimen_fiscal_clave');

            // Tipo de persona si no existe
            if (!Schema::hasColumn('proveedores', 'tipo_persona')) {
                $table->string('tipo_persona')->nullable()->after('logo');
            }

            // constancia_fiscal si no existe
            if (!Schema::hasColumn('proveedores', 'constancia_fiscal')) {
                $table->string('constancia_fiscal')->nullable()->after('regimen_fiscal_nombre');
            }

            // 
            if (!Schema::hasColumn('proveedores', 'constancia_fiscal')) {
                $table->string('constancia_fiscal')->nullable()->after('regimen_fiscal_nombre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn('regimen_fiscal_clave');
            $table->dropColumn('regimen_fiscal_nombre');

            if (Schema::hasColumn('proveedores', 'tipo_persona')) {
                $table->dropColumn('tipo_persona');
            }

            if (Schema::hasColumn('proveedores', 'constancia_fiscal')) {
                $table->dropColumn('constancia_fiscal');
            }
        });
    }
};
