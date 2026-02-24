<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\SolicitudPago;
use App\Models\Proveedor;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {
            $table->unsignedBigInteger('usuario_creador_id')
                ->nullable()
                ->after('usuario_id');

            $table->foreign('usuario_creador_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // ⚠️ Iterar y asignar usuario principal del proveedor
        SolicitudPago::with(['proveedor.userProveedores.user'])
            ->chunk(200, function ($spps) {
                foreach ($spps as $spp) {

                    $proveedor = $spp->proveedor;

                    if (!$proveedor) {
                        continue;
                    }

                    $usuarioPrincipal = $proveedor->usuarioPrincipal();

                    if ($usuarioPrincipal) {
                        DB::connection('mysql5')
                            ->table('solicitudes_pago')
                            ->where('id', $spp->id)
                            ->update([
                                'usuario_creador_id' => $usuarioPrincipal->id
                            ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::connection('mysql5')->table('solicitudes_pago', function (Blueprint $table) {
            $table->dropForeign(['usuario_creador_id']);
            $table->dropColumn('usuario_creador_id');
        });
    }
};
