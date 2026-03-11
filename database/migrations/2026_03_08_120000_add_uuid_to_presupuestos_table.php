<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Rellenar UUID para registros existentes
        $presupuestos = \DB::table('presupuestos')->whereNull('uuid')->get();
        foreach ($presupuestos as $p) {
            \DB::table('presupuestos')
                ->where('id', $p->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
