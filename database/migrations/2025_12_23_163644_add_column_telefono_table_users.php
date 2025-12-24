<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1️⃣ Agregar la columna
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono')->nullable()->after('email');
        });

        // 2️⃣ Copiar email → telefono
        DB::table('users')->update([
            'telefono' => DB::raw('email')
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telefono');
        });
    }
};
