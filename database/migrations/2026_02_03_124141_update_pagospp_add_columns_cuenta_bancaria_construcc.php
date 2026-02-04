`<?php

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
            Schema::table('pagos_spp', function (Blueprint $table) {
                $table->foreignId('cuenta_destino_id')->nullable()->constrained('cuentas_bancarias')->nullOnDelete()->comment('ID de la cuenta destino del proveedor')->after('cuenta_bancaria_empresa_construcc_id');
                $table->string('cuenta_destino_terminacion')->nullable()->after('cuenta_destino_id');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::table('pagos_spp', function (Blueprint $table) {
                $table->dropColumn('cuenta_destino_id');
                $table->dropColumn('cuenta_destino_terminacion');
            });
        }
    };
