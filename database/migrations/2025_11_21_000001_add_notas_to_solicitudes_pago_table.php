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
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->text('notas')->nullable()->after('observaciones');
            $table->string('utilizara')->nullable()->after('notas');
            $table->string('equipo')->nullable()->after('utilizara');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn(['notas', 'utilizara', 'equipo']);
        });
    }
};
