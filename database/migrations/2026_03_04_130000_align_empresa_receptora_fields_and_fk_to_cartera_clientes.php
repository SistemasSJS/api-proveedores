<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->string('empresa_receptora_puesto')->nullable()->after('empresa_receptora_nombre');
            $table->string('empresa_receptora_empresa')->nullable()->after('empresa_receptora_puesto');
        });

        DB::table('presupuestos')->update([
            'empresa_receptora_puesto' => DB::raw('empresa_receptora_rfc'),
            'empresa_receptora_empresa' => DB::raw('empresa_receptora_direccion'),
        ]);

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropForeign(['empresa_receptora_id']);
            $table->foreign('empresa_receptora_id')
                ->references('id')
                ->on('cartera_clientes')
                ->nullOnDelete();

            if (Schema::hasColumn('presupuestos', 'cartera_cliente_id')) {
                $table->dropConstrainedForeignId('cartera_cliente_id');
            }

            $columnsToDrop = [];
            foreach (['cliente_nombre', 'cliente_puesto', 'cliente_empresa'] as $column) {
                if (Schema::hasColumn('presupuestos', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            if (Schema::hasColumn('presupuestos', 'empresa_receptora_rfc')) {
                $table->dropColumn('empresa_receptora_rfc');
            }
            if (Schema::hasColumn('presupuestos', 'empresa_receptora_direccion')) {
                $table->dropColumn('empresa_receptora_direccion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->string('empresa_receptora_rfc', 20)->nullable()->after('empresa_receptora_nombre');
            $table->text('empresa_receptora_direccion')->nullable()->after('empresa_receptora_rfc');
        });

        DB::table('presupuestos')->update([
            'empresa_receptora_rfc' => DB::raw('empresa_receptora_puesto'),
            'empresa_receptora_direccion' => DB::raw('empresa_receptora_empresa'),
        ]);

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropForeign(['empresa_receptora_id']);
            $table->foreign('empresa_receptora_id')
                ->references('id')
                ->on('proveedores')
                ->nullOnDelete();

            $table->dropColumn(['empresa_receptora_puesto', 'empresa_receptora_empresa']);

            $table->foreignId('cartera_cliente_id')->nullable()->after('empresa_receptora_id')->constrained('cartera_clientes')->nullOnDelete();
            $table->string('cliente_nombre')->nullable()->after('empresa_receptora_correo');
            $table->string('cliente_puesto')->nullable()->after('cliente_nombre');
            $table->string('cliente_empresa')->nullable()->after('cliente_puesto');
        });
    }
};
