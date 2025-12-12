<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {
            $table->boolean('visto_rechazada')->default(false)->after('estado_solicitud');
        });
    }

    public function down()
    {
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn('visto_rechazada');
        });
    }
};
