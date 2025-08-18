<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_audits', function (Blueprint $table) {
            $table->integer('numero_registros_procesados')->default(0)
                ->comment('Contador de registros procesados exitosamente');
        });
    }

    public function down(): void
    {
        Schema::table('import_audits', function (Blueprint $table) {
            $table->dropColumn('numero_registros_procesados');
        });
    }
};
