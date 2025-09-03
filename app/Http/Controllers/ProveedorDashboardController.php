<?php

namespace App\Http\Controllers;

use App\Enums\EstadoGeneral;
use App\Http\Resources\ProveedorDashboard\ProveedorDashboardCotizacionResource;
use App\Models\Cotizacion;
use App\Models\Proveedor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;


class ProveedorDashboardController extends Controller
{
    use ApiResponse;

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


    public function cotizacionesDashboard(Request $request, Proveedor $proveedor)
    {
        $fields = Cotizacion::getFilters();
        $filters = $request->only($fields);

        $sortBy = $request->input('sort_by', 'fecha_cotizacion');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 10);

        $query = Cotizacion::query()
            ->filter($filters)
            ->where('proveedor_id', $proveedor)
            ->orderBy($sortBy, $order);

        $paginator = $query->paginate($perPage);

        // Transformación con Resource
        $data = ProveedorDashboardCotizacionResource::collection($paginator)->resolve();
        return $this->paginated($paginator->setCollection(collect($data)));
    }
}
