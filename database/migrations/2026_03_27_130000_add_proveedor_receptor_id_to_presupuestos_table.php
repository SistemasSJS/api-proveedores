<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Id del proveedor del catálogo cuando el receptor no es cartera (empresa_receptora_id solo FK a cartera_clientes).
     */
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->foreignId('proveedor_receptor_id')
                ->nullable()
                ->after('empresa_receptora_id')
                ->constrained('proveedores')
                ->nullOnDelete();
        });

        $rows = DB::table('presupuestos')
            ->whereNotNull('configuracion_condiciones')
            ->get(['id', 'configuracion_condiciones']);

        foreach ($rows as $row) {
            $raw = $row->configuracion_condiciones;
            $config = is_string($raw) ? json_decode($raw, true) : [];
            if (! is_array($config) || empty($config['proveedor_receptor_id'])) {
                continue;
            }
            $pid = (int) $config['proveedor_receptor_id'];
            if ($pid <= 0) {
                continue;
            }
            unset($config['proveedor_receptor_id']);
            DB::table('presupuestos')->where('id', $row->id)->update([
                'proveedor_receptor_id' => $pid,
                'configuracion_condiciones' => json_encode($config),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropForeign(['proveedor_receptor_id']);
            $table->dropColumn('proveedor_receptor_id');
        });
    }
};
