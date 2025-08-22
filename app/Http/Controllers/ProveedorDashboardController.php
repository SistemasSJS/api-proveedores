<?php

namespace App\Http\Controllers;

use App\Enums\EstadoGeneral;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Request;

class ProveedorDashboardController extends Controller
{
    public function getStats(Request $request, Proveedor $proveedor)
    {
        $stats = [
            // 'productos_activos' => $proveedor->productos()->where('estatus', EstadoGeneral::ACTIVO->value)->count(),
            // 'sucursales_activas' => $proveedor->sucursales()->where('estatus', EstadoGeneral::ACTIVO->value)->count(),
            // 'requisiciones_pendientes' => $proveedor->requisiciones()->where('estatus', 'pendiente')->count(),
            // 'requisiciones_mes' => $proveedor->requisiciones()->whereMonth('created_at', now()->month)->count(),

            'usuarios' => $proveedor->usuariosActivos()->count(),
            'productos' => $proveedor->productos()->where('estatus', EstadoGeneral::ACTIVO->value)->count(),
            'categorias' => $proveedor->categorias()->where('estatus', EstadoGeneral::ACTIVO->value)->count(),
            'marcas' => $proveedor->marcas()->where('estatus', EstadoGeneral::ACTIVO->value)->count(),
            'sucursales' => $proveedor->sucursalesActivas()->count(),
            'unidadesMedida' => $proveedor->unidades()->where('estatus', EstadoGeneral::ACTIVO->value)->count(),

        ];

        return $this->success([
            'stats' => $stats,
        ]);
    }
}
