<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {
            // Renombrar columnas CFDI a minúsculas
            $table->renameColumn('USO', 'uso');
            $table->renameColumn('MP', 'mp');
            $table->renameColumn('FP', 'fp');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {
            // Revertir a nombres originales en mayúsculas
            $table->renameColumn('uso', 'USO');
            $table->renameColumn('mp', 'MP');
            $table->renameColumn('fp', 'FP');
        });
    }
};
