<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {

            // =========================
            // Datos fiscales CFDI 4.0
            // =========================
            $table->string('rfc', 13)->nullable()->after('folio_factura');
            $table->string('nombre_fiscal')->nullable()->after('rfc');
            $table->string('regimen_fiscal', 3)->nullable()->after('nombre_fiscal');
            $table->string('codigo_postal', 5)->nullable()->after('regimen_fiscal');

            $table->string('uso_cfdi', 3)->default('G03')->after('codigo_postal');
            $table->string('metodo_pago', 3)->default('PUE')->after('uso_cfdi');
            $table->string('forma_pago', 2)->default('01')->after('metodo_pago');

            $table->string('email_factura')->nullable()->after('forma_pago');

            // =========================
            // Tracking de carga factura
            // =========================
            $table->timestamp('fecha_subida_factura_xml')->nullable()->after('ruta_archivo_factura_xml');
            $table->timestamp('fecha_subida_factura_pdf')->nullable()->after('ruta_archivo_factura_pdf');

            $table->unsignedBigInteger('usuario_construcc_subio_factura_id')->nullable()->after('fecha_subida_factura_pdf');
            $table->string('usuario_construcc_subio_factura_rol', 50)->nullable()->after('usuario_construcc_subio_factura_id');

            // Índices útiles
            $table->index('rfc');
            $table->index('usuario_construcc_subio_factura_id');
            $table->index('uso_cfdi');
            $table->index('metodo_pago');
            $table->index('forma_pago');

        });
    }

    public function down(): void
    {
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {
            $table->dropColumn([
                'rfc',
                'nombre_fiscal',
                'regimen_fiscal',
                'codigo_postal',
                'uso_cfdi',
                'metodo_pago',
                'forma_pago',
                'email_factura',
                'fecha_subida_factura_xml',
                'fecha_subida_factura_pdf',
                'usuario_construcc_subio_factura_id',
                'usuario_construcc_subio_factura_rol',
            ]);
        });
    }
};
