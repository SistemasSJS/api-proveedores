<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\EstadoCuentaBancaria;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cuentas_bancarias', function (Blueprint $table) {
            // Campo de estatus basado en el enum EstadoCuentaBancaria
            $table->enum('estatus', EstadoCuentaBancaria::values())
                ->default(EstadoCuentaBancaria::ACTIVA->value)->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('cuentas_bancarias', function (Blueprint $table) {
            $table->dropColumn('estatus');
        });
    }
};
