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
        Schema::table('cartera_clientes', function (Blueprint $table) {
            $table->string('alias_empresa')->nullable()->after('empresa');
        });

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->string('empresa_receptora_alias')->nullable()->after('empresa_receptora_empresa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cartera_clientes', function (Blueprint $table) {
            $table->dropColumn('alias_empresa');
        });

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropColumn('empresa_receptora_alias');
        });
    }
};
