<?php

namespace App\Http\Controllers;

use App\Http\Resources\RequisicionResource;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Request;

class ProveedorDashboardController extends Controller
{
    public function getStats(Request $request, Proveedor $proveedor)
    {
        $stats = [
            'productos_activos' => $proveedor->productos()->where('activo', true)->count(),
            'sucursales_activas' => $proveedor->sucursales()->count(),
            'requisiciones_pendientes' => $proveedor->requisiciones()->where('estatus', 'pendiente')->count(),
            'requisiciones_mes' => $proveedor->requisiciones()->whereMonth('created_at', now()->month)->count(),
        ];

        $requisiciones_recientes = $proveedor->requisiciones()
            ->with(['usuario', 'detalles'])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'requisiciones_recientes' => RequisicionResource::collection($requisiciones_recientes),
        ]);
    }
}
