<?php

namespace App\Http\Controllers;

use App\Enums\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenCompraController extends Controller
{
    /**
     * Listado de órdenes de compra con paginación y filtros
     */
    public function index(Request $request, Proveedor $proveedor): JsonResponse
    {
        $filters = $request->only(OrdenCompra::getFilters());
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 15);

        $query = OrdenCompra::query()
            ->with(OrdenCompra::eagerLodable())
            ->where('proveedor_id', $proveedor->id)
            ->filter($filters)
            ->orderBy($sortBy, $order);

        // Agregar días sin SP como campo calculado
        $query->selectRaw('ordenes_compra.*, 
            CASE 
                WHEN sp_count > 0 THEN 0
                ELSE DATEDIFF(NOW(), COALESCE(fecha_aprobacion, created_at))
            END as dias_sin_sp');

        $originalPaginator = $query->paginate($perPage);

        // Agregar nivel de alerta a cada OC
        $data = $originalPaginator->getCollection()->map(function ($oc) {
            $oc->nivel_alerta = $oc->getNivelAlerta();
            $oc->monto_disponible = $oc->getMontoDisponible();
            $oc->puede_generar_sp = $oc->puedeGenerarSolicitudPago();
            return $oc;
        });

        return $this->paginated($originalPaginator->setCollection($data));
    }

    /**
     * Mostrar detalle de una orden de compra específica
     */
    public function show(Proveedor $proveedor, OrdenCompra $ordenCompra): JsonResponse
    {
        if ($ordenCompra->proveedor_id !== $proveedor->id) {
            return $this->error('Orden de compra no pertenece a este proveedor', 403);
        }

        $ordenCompra->load([
            'detalles',
            'proveedor',
            'empresaConstrucc',
            'solicitudesPago' => function ($query) {
                $query->with(['proveedor', 'empresaConstrucc']);
            }
        ]);

        // Agregar datos calculados
        $ordenCompra->nivel_alerta = $ordenCompra->getNivelAlerta();
        $ordenCompra->dias_sin_sp = $ordenCompra->getDiasSinSolicitudPago();
        $ordenCompra->monto_disponible = $ordenCompra->getMontoDisponible();
        $ordenCompra->puede_generar_sp = $ordenCompra->puedeGenerarSolicitudPago();

        return $this->success($ordenCompra);
    }

    /**
     * Obtener estadísticas para el dashboard
     */
    public function getEstadisticas(Request $request, Proveedor $proveedor): JsonResponse
    {
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $query = OrdenCompra::where('proveedor_id', $proveedor->id);

        if ($fechaDesde) {
            $query->where('fecha_orden', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->where('fecha_orden', '<=', $fechaHasta);
        }

        // Estadísticas básicas
        $stats = [
            'total_oc' => $query->count(),
            'total_importe' => $query->sum('importe_total'),
            'pendientes' => $query->where('estado', EstadoOrdenCompra::PENDIENTE)->count(),
            'aprobadas' => $query->where('estado', EstadoOrdenCompra::APROBADA)->count(),
            'rechazadas' => $query->where('estado', EstadoOrdenCompra::RECHAZADA)->count(),
            'completadas' => $query->where('estado', EstadoOrdenCompra::COMPLETADA)->count(),
            'con_sp' => $query->where('sp_count', '>', 0)->count(),
            'sin_sp' => $query->where('sp_count', 0)->count(),
        ];

        // Distribución por estado
        $distribucionEstados = $query->select('estado', DB::raw('count(*) as cantidad'))
            ->groupBy('estado')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->estado => $item->cantidad];
            });

        // OC con alertas
        $conAlertas = $query->where('sp_count', 0)
            ->where('estado', EstadoOrdenCompra::APROBADA)
            ->selectRaw('*, DATEDIFF(NOW(), COALESCE(fecha_aprobacion, created_at)) as dias_sin_sp')
            ->get()
            ->filter(function ($oc) {
                return $oc->dias_sin_sp >= 7;
            })
            ->count();

        // Montos
        $montosPorEstado = $query->select('estado', DB::raw('sum(importe_total) as total_importe'))
            ->groupBy('estado')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->estado => (float) $item->total_importe];
            });

        return $this->success([
            'estadisticas_basicas' => $stats,
            'distribucion_estados' => $distribucionEstados,
            'montos_por_estado' => $montosPorEstado,
            'alertas' => [
                'con_alertas' => $conAlertas,
                'sin_alertas' => $stats['sin_sp'] - $conAlertas
            ]
        ]);
    }

    /**
     * Obtener solicitudes de pago de una orden de compra específica
     */
    public function getSolicitudesPago(Proveedor $proveedor, OrdenCompra $ordenCompra): JsonResponse
    {
        if ($ordenCompra->proveedor_id !== $proveedor->id) {
            return $this->error('Orden de compra no pertenece a este proveedor', 403);
        }

        $solicitudesPago = $ordenCompra->solicitudesPago()
            ->with(['proveedor', 'empresaConstrucc', 'cuentasBancarias'])
            ->get()
            ->map(function ($sp) {
                $sp->monto_asociado_oc = $sp->pivot->monto_asociado;
                $sp->fecha_vinculacion_oc = $sp->pivot->fecha_vinculacion;
                $sp->notas_vinculacion = $sp->pivot->notas;
                return $sp;
            });

        return $this->success([
            'orden_compra' => [
                'id' => $ordenCompra->id,
                'numero_orden' => $ordenCompra->numero_orden,
                'importe_total' => $ordenCompra->importe_total,
                'monto_sp_asociado' => $ordenCompra->monto_sp_asociado,
                'monto_disponible' => $ordenCompra->getMontoDisponible()
            ],
            'solicitudes_pago' => $solicitudesPago
        ]);
    }

    /**
     * Obtener órdenes de compra disponibles para conversión a SP
     */
    public function getOrdenesDisponibles(Request $request, Proveedor $proveedor): JsonResponse
    {
        $perPage = $request->input('per_page', 10);

        $ordenes = OrdenCompra::where('proveedor_id', $proveedor->id)
            ->disponiblesParaConversion()
            ->with(['empresaConstrucc', 'detalles'])
            ->selectRaw('*, 
                (importe_total - monto_sp_asociado) as monto_disponible,
                DATEDIFF(NOW(), COALESCE(fecha_aprobacion, created_at)) as dias_sin_sp')
            ->orderBy('fecha_aprobacion', 'asc')
            ->paginate($perPage);

        // Agregar datos calculados
        $data = $ordenes->getCollection()->map(function ($oc) {
            $oc->nivel_alerta = $oc->getNivelAlerta();
            $oc->puede_generar_sp = $oc->puedeGenerarSolicitudPago();
            return $oc;
        });

        return $this->paginated($ordenes->setCollection($data));
    }

    /**
     * Obtener contadores de SP por OC
     */
    public function getContadores(Request $request, Proveedor $proveedor): JsonResponse
    {
        $numeroOrden = $request->input('numero_orden');

        if (!$numeroOrden) {
            return $this->error('Número de orden requerido', 422);
        }

        $ordenCompra = OrdenCompra::where('proveedor_id', $proveedor->id)
            ->where('numero_orden', $numeroOrden)
            ->with(['solicitudesPago'])
            ->first();

        if (!$ordenCompra) {
            return $this->error('Orden de compra no encontrada', 404);
        }

        return $this->success([
            'numero_orden' => $ordenCompra->numero_orden,
            'sp_count' => $ordenCompra->sp_count,
            'monto_total' => $ordenCompra->importe_total,
            'monto_sp_asociado' => $ordenCompra->monto_sp_asociado,
            'monto_disponible' => $ordenCompra->getMontoDisponible(),
            'estado' => $ordenCompra->estado,
            'puede_generar_sp' => $ordenCompra->puedeGenerarSolicitudPago(),
            'solicitudes_pago' => $ordenCompra->solicitudesPago->map(function ($sp) {
                return [
                    'id' => $sp->id,
                    'numero_folio_solicitud' => $sp->numero_folio_solicitud,
                    'monto_total' => $sp->monto_total,
                    'estado_solicitud' => $sp->estado_solicitud,
                    'monto_asociado' => $sp->pivot->monto_asociado
                ];
            })
        ]);
    }

    /**
     * Obtener órdenes de compra sin solicitudes de pago (para alertas)
     */
    public function getOrdenesSinSolicitudes(Request $request, Proveedor $proveedor): JsonResponse
    {
        $diasMinimo = $request->input('dias_minimo', 7);
        $perPage = $request->input('per_page', 10);

        $ordenes = OrdenCompra::where('proveedor_id', $proveedor->id)
            ->sinSolicitudesPago()
            ->aprobadas()
            ->with(['empresaConstrucc'])
            ->selectRaw('*, 
                DATEDIFF(NOW(), COALESCE(fecha_aprobacion, created_at)) as dias_sin_sp')
            ->havingRaw('dias_sin_sp >= ?', [$diasMinimo])
            ->orderByRaw('dias_sin_sp DESC')
            ->paginate($perPage);

        // Agregar nivel de alerta
        $data = $ordenes->getCollection()->map(function ($oc) {
            $oc->nivel_alerta = $oc->getNivelAlerta();
            return $oc;
        });

        return $this->paginated($ordenes->setCollection($data));
    }
}
