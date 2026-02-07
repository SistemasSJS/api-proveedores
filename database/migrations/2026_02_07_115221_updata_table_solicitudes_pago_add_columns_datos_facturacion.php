<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {

            // 1. Eliminar columnas fiscales obsoletas
            $table->dropColumn([
                'rfc',
                'nombre_fiscal',
                'regimen_fiscal',
                'codigo_postal',
                'email_factura',
            ]);

            // 2. Agregar referencia a datos de facturación
            $table->unsignedBigInteger('datos_facturacion_id')
                ->nullable()
                ->after('folio_factura');

            // 3. Renombrar columnas CFDI
            $table->renameColumn('uso_cfdi', 'USO');
            $table->renameColumn('metodo_pago', 'MP');
            $table->renameColumn('forma_pago', 'FP');
        });

        // 4. Asegurar que USO, MP, FP acepten NULL
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {
            $table->string('USO', 3)->nullable()->change();
            $table->string('MP', 3)->nullable()->change();
            $table->string('FP', 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {

            // Revertir nombres de columnas
            $table->renameColumn('USO', 'uso_cfdi');
            $table->renameColumn('MP', 'metodo_pago');
            $table->renameColumn('FP', 'forma_pago');

            // Eliminar FK
            // $table->dropForeign(['datos_facturacion_id']);

            // Eliminar datos_facturacion_id
            $table->dropColumn('datos_facturacion_id');

            // Restaurar columnas eliminadas
            $table->string('rfc', 13)->nullable()->after('folio_factura');
            $table->string('nombre_fiscal')->nullable()->after('rfc');
            $table->string('regimen_fiscal', 3)->nullable()->after('nombre_fiscal');
            $table->string('codigo_postal', 5)->nullable()->after('regimen_fiscal');
            $table->string('email_factura')->nullable()->after('FP');

            // Restaurar defaults originales si los ocupas
            $table->string('uso_cfdi', 3)->default('G03')->change();
            $table->string('metodo_pago', 3)->default('PUE')->change();
            $table->string('forma_pago', 2)->default('01')->change();
        });
    }
};
