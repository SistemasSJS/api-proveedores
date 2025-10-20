<?php

namespace App\Http\Controllers;

use App\Enums\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\SolicitudPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProveedorOrdenCompraDashboardController extends Controller
{
    /**
     * Dashboard principal - estadísticas y resumen de OC
     */
    public function dashboard(Request $request, Proveedor $proveedor): JsonResponse
    {
        $fechaDesde = $request->input('fecha_desde', now()->subDays(30)->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));

        // Estadísticas básicas
        $stats = $this->getEstadisticasBasicas($proveedor, $fechaDesde, $fechaHasta);

        // Distribuciones
        $distribuciones = $this->getDistribuciones($proveedor, $fechaDesde, $fechaHasta);

        // Alertas y notificaciones
        $alertas = $this->getAlertas($proveedor);

        // OC recientes
        $ordenesRecientes = $this->getOrdenesRecientes($proveedor, 5);

        // Tendencias
        $tendencias = $this->getTendenciasMensuales($proveedor);

        return $this->success([
            'periodo' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ],
            'estadisticas' => $stats,
            'distribuciones' => $distribuciones,
            'alertas' => $alertas,
            'ordenes_recientes' => $ordenesRecientes,
            'tendencias' => $tendencias,
        ]);
    }

    /**
     * Estado general de OC y SP para el proveedor
     */
    public function estadoGeneral(Request $request, Proveedor $proveedor): JsonResponse
    {
        $statsOC = OrdenCompra::where('proveedor_id', $proveedor->id)
            ->selectRaw('
                COUNT(*) as total_oc,
                SUM(importe_total) as importe_total_oc,
                SUM(monto_sp_asociado) as monto_convertido,
                SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN sp_count = 0 THEN 1 ELSE 0 END) as sin_sp
            ', [EstadoOrdenCompra::PENDIENTE->value, EstadoOrdenCompra::APROBADA->value])
            ->first();

        $statsSP = SolicitudPago::where('proveedor_id', $proveedor->id)
            ->selectRaw('
                COUNT(*) as total_sp,
                SUM(monto_total) as importe_total_sp,
                SUM(CASE WHEN estado_solicitud = "pendiente" THEN 1 ELSE 0 END) as sp_pendientes,
                SUM(CASE WHEN estado_solicitud = "autorizada" THEN 1 ELSE 0 END) as sp_autorizadas,
                SUM(CASE WHEN estado_solicitud = "pagada" THEN 1 ELSE 0 END) as sp_pagadas,
                SUM(CASE WHEN origen_oc = true THEN 1 ELSE 0 END) as sp_desde_oc
            ')
            ->first();

        return $this->success([
            'ordenes_compra' => [
                'total' => (int) $statsOC->total_oc,
                'importe_total' => (float) $statsOC->importe_total_oc,
                'monto_convertido' => (float) $statsOC->monto_convertido,
                'monto_disponible' => (float) ($statsOC->importe_total_oc - $statsOC->monto_convertido),
                'pendientes' => (int) $statsOC->pendientes,
                'aprobadas' => (int) $statsOC->aprobadas,
                'sin_solicitudes' => (int) $statsOC->sin_sp,
                'tasa_conversion' => $statsOC->total_oc > 0 ? (($statsOC->total_oc - $statsOC->sin_sp) / $statsOC->total_oc) * 100 : 0,
            ],
            'solicitudes_pago' => [
                'total' => (int) $statsSP->total_sp,
                'importe_total' => (float) $statsSP->importe_total_sp,
                'pendientes' => (int) $statsSP->sp_pendientes,
                'autorizadas' => (int) $statsSP->sp_autorizadas,
                'pagadas' => (int) $statsSP->sp_pagadas,
                'desde_oc' => (int) $statsSP->sp_desde_oc,
                'directas' => (int) ($statsSP->total_sp - $statsSP->sp_desde_oc),
            ],
        ]);
    }

    /**
     * Actividad reciente del proveedor
     */
    public function actividadReciente(Request $request, Proveedor $proveedor): JsonResponse
    {
        $limite = $request->input('limite', 10);
        $dias = $request->input('dias', 7);

        // Combinar OC y SP recientes
        $ocRecientes = OrdenCompra::where('proveedor_id', $proveedor->id)
            ->where('created_at', '>=', now()->subDays($dias))
            ->with(['empresaConstrucc'])
            ->get()
            ->map(function ($oc) {
                return [
                    'tipo' => 'orden_compra',
                    'id' => $oc->id,
                    'titulo' => "OC #{$oc->numero_orden}",
                    'descripcion' => "Orden por \${$oc->importe_total} - {$oc->empresaConstrucc->nombre}",
                    'fecha' => $oc->created_at,
                    'estado' => $oc->estado,
                    'metadata' => [
                        'numero_orden' => $oc->numero_orden,
                        'importe' => $oc->importe_total,
                        'empresa' => $oc->empresaConstrucc->nombre,
                    ],
                ];
            });

        $spRecientes = SolicitudPago::where('proveedor_id', $proveedor->id)
            ->where('created_at', '>=', now()->subDays($dias))
            ->with(['empresaConstrucc'])
            ->get()
            ->map(function ($sp) {
                return [
                    'tipo' => 'solicitud_pago',
                    'id' => $sp->id,
                    'titulo' => "SP #{$sp->numero_folio_solicitud}",
                    'descripcion' => "Solicitud por \${$sp->monto_total} - {$sp->empresaConstrucc->nombre}",
                    'fecha' => $sp->created_at,
                    'estado' => $sp->estado_solicitud,
                    'metadata' => [
                        'numero_folio' => $sp->numero_folio_solicitud,
                        'monto' => $sp->monto_total,
                        'empresa' => $sp->empresaConstrucc->nombre,
                        'origen_oc' => $sp->origen_oc ?? false,
                    ],
                ];
            });

        $actividad = $ocRecientes->concat($spRecientes)
            ->sortByDesc('fecha')
            ->take($limite)
            ->values();

        return $this->success([
            'actividad' => $actividad,
            'total_elementos' => $actividad->count(),
            'periodo_dias' => $dias,
        ]);
    }

    /**
     * Métricas de rendimiento
     */
    public function metricas(Request $request, Proveedor $proveedor): JsonResponse
    {
        $fechaDesde = $request->input('fecha_desde', now()->subDays(30)->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));

        // Tiempo promedio de conversión OC -> SP
        $tiempoConversion = DB::table('ordenes_compra')
            ->join('orden_compra_solicitud_pago', 'ordenes_compra.id', '=', 'orden_compra_solicitud_pago.orden_compra_id')
            ->join('solicitudes_pago', 'orden_compra_solicitud_pago.solicitud_pago_id', '=', 'solicitudes_pago.id')
            ->where('ordenes_compra.proveedor_id', $proveedor->id)
            ->whereBetween('ordenes_compra.fecha_orden', [$fechaDesde, $fechaHasta])
            ->selectRaw('AVG(DATEDIFF(solicitudes_pago.created_at, ordenes_compra.fecha_orden)) as promedio_dias')
            ->value('promedio_dias');

        // Eficiencia de conversión por mes
        $eficienciaMensual = OrdenCompra::where('proveedor_id', $proveedor->id)
            ->whereBetween('fecha_orden', [$fechaDesde, $fechaHasta])
            ->selectRaw('
                DATE_FORMAT(fecha_orden, "%Y-%m") as mes,
                COUNT(*) as total_oc,
                SUM(CASE WHEN sp_count > 0 THEN 1 ELSE 0 END) as oc_convertidas,
                AVG(CASE WHEN sp_count > 0 THEN DATEDIFF(NOW(), fecha_orden) ELSE NULL END) as dias_promedio_conversion
            ')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->map(function ($item) {
                return [
                    'mes' => $item->mes,
                    'total_oc' => (int) $item->total_oc,
                    'oc_convertidas' => (int) $item->oc_convertidas,
                    'tasa_conversion' => $item->total_oc > 0 ? ($item->oc_convertidas / $item->total_oc) * 100 : 0,
                    'dias_promedio_conversion' => (float) $item->dias_promedio_conversion,
                ];
            });

        return $this->success([
            'periodo' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ],
            'tiempo_promedio_conversion' => (float) $tiempoConversion,
            'eficiencia_mensual' => $eficienciaMensual,
        ]);
    }

    // Métodos privados para organizar el código

    private function getEstadisticasBasicas(Proveedor $proveedor, string $fechaDesde, string $fechaHasta)
    {
        $query = OrdenCompra::where('proveedor_id', $proveedor->id)
            ->whereBetween('fecha_orden', [$fechaDesde, $fechaHasta]);

        return [
            'total_oc' => $query->count(),
            'importe_total' => $query->sum('importe_total'),
            'pendientes' => $query->where('estado', EstadoOrdenCompra::PENDIENTE)->count(),
            'aprobadas' => $query->where('estado', EstadoOrdenCompra::APROBADA)->count(),
            'con_sp' => $query->where('sp_count', '>', 0)->count(),
            'sin_sp' => $query->where('sp_count', 0)->count(),
        ];
    }

    private function getDistribuciones(Proveedor $proveedor, string $fechaDesde, string $fechaHasta)
    {
        $query = OrdenCompra::where('proveedor_id', $proveedor->id)
            ->whereBetween('fecha_orden', [$fechaDesde, $fechaHasta]);

        return $this->success($query);
        // return [
        //     'por_estado' => $query->select('estado', DB::raw('count(*) as cantidad'))
        //         ->groupBy('estado')->get()->pluck('cantidad', 'estado'),
        //     'por_empresa' => $query->join('empresas_construcc', 'ordenes_compra.empresa_construcc_id', '=', 'empresas_construcc.id')
        //         ->select('empresas_construcc.nombre', DB::raw('count(*) as cantidad'))
        //         ->groupBy('empresas_construcc.id', 'empresas_construcc.nombre')
        //         ->orderBy('cantidad', 'desc')
        //         ->limit(5)->get(),
        // ];
    }

    private function getAlertas(Proveedor $proveedor)
    {
        $conAlertas = OrdenCompra::where('proveedor_id', $proveedor->id)
            ->where('sp_count', 0)
            ->where('estado', EstadoOrdenCompra::APROBADA)
            ->get()
            ->filter(function ($oc) {
                return $oc->getDiasSinSolicitudPago() >= 7;
            });

        return [
            'total' => $conAlertas->count(),
            'criticas' => $conAlertas->filter(fn($oc) => $oc->getDiasSinSolicitudPago() >= 15)->count(),
            'advertencias' => $conAlertas->filter(fn($oc) => $oc->getDiasSinSolicitudPago() >= 7 && $oc->getDiasSinSolicitudPago() < 15)->count(),
        ];
    }

    private function getOrdenesRecientes(Proveedor $proveedor, int $limite)
    {
        return OrdenCompra::where('proveedor_id', $proveedor->id)
            ->with(['empresaConstrucc'])
            ->orderBy('created_at', 'desc')
            ->limit($limite)
            ->get()
            ->map(function ($oc) {
                return [
                    'id' => $oc->id,
                    'numero_orden' => $oc->numero_orden,
                    'importe_total' => $oc->importe_total,
                    'estado' => $oc->estado,
                    'empresa' => $oc->empresaConstrucc->nombre,
                    'fecha_orden' => $oc->fecha_orden,
                    'sp_count' => $oc->sp_count,
                    'puede_generar_sp' => $oc->puedeGenerarSolicitudPago(),
                ];
            })->all();
    }

    private function getTendenciasMensuales(Proveedor $proveedor)
    {
        return OrdenCompra::where('proveedor_id', $proveedor->id)
            ->where('fecha_orden', '>=', now()->subMonths(6))
            ->selectRaw('
                DATE_FORMAT(fecha_orden, "%Y-%m") as mes,
                COUNT(*) as cantidad,
                SUM(importe_total) as importe
            ')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->map(function ($item) {
                return [
                    'mes' => $item->mes,
                    'cantidad' => (int) $item->cantidad,
                    'importe' => (float) $item->importe,
                ];
            })->all();
    }

    /**
     * Estadísticas generales para rutas legacy
     */
    public function estadisticas(Request $request, Proveedor $proveedor): JsonResponse
    {
        $fechaDesde = $request->input('fecha_desde', now()->subDays(30)->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));

        $stats = $this->getEstadisticasBasicas($proveedor, $fechaDesde, $fechaHasta);

        return $this->success([
            'periodo' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ],
            'estadisticas' => $stats,
        ]);
    }

    /**
     * Listado de órdenes de compra sin solicitudes de pago
     */
    public function ordenesSinSolicitudes(Request $request, Proveedor $proveedor): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        
        $query = OrdenCompra::where('proveedor_id', $proveedor->id)
            ->where('sp_count', 0)
            ->with(['empresaConstrucc']);

        // Aplicar filtros opcionales
        if ($request->has('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        if ($request->has('fecha_desde')) {
            $query->where('fecha_orden', '>=', $request->input('fecha_desde'));
        }

        if ($request->has('fecha_hasta')) {
            $query->where('fecha_orden', '<=', $request->input('fecha_hasta'));
        }

        $ordenes = $query->orderBy('fecha_orden', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return $this->success([
            'ordenes' => $ordenes->items(),
            'pagination' => [
                'current_page' => $ordenes->currentPage(),
                'last_page' => $ordenes->lastPage(),
                'per_page' => $ordenes->perPage(),
                'total' => $ordenes->total(),
                'from' => $ordenes->firstItem(),
                'to' => $ordenes->lastItem(),
            ],
        ]);
    }

    /**
     * Contadores rápidos para widgets
     */
    public function contadores(Request $request, Proveedor $proveedor): JsonResponse
    {
        $totalOC = OrdenCompra::where('proveedor_id', $proveedor->id)->count();
        $totalSP = SolicitudPago::where('proveedor_id', $proveedor->id)->count();
        
        $ocPendientes = OrdenCompra::where('proveedor_id', $proveedor->id)
            ->where('estado', EstadoOrdenCompra::PENDIENTE)
            ->count();
            
        $ocSinSP = OrdenCompra::where('proveedor_id', $proveedor->id)
            ->where('sp_count', 0)
            ->count();
            
        $spPendientes = SolicitudPago::where('proveedor_id', $proveedor->id)
            ->where('estado_solicitud', 'pendiente')
            ->count();

        return $this->success([
            'total_oc' => $totalOC,
            'total_sp' => $totalSP,
            'oc_pendientes' => $ocPendientes,
            'oc_sin_sp' => $ocSinSP,
            'sp_pendientes' => $spPendientes,
        ]);
    }

    // === MÉTODOS PRINCIPALES DE CONSULTA ===

    /**
     * Listado de órdenes de compra con paginación y filtros
     * Dashboard con listado y filtros
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
            },
        ]);

        // Agregar datos calculados
        $ordenCompra->nivel_alerta = $ordenCompra->getNivelAlerta();
        $ordenCompra->dias_sin_sp = $ordenCompra->getDiasSinSolicitudPago();
        $ordenCompra->monto_disponible = $ordenCompra->getMontoDisponible();
        $ordenCompra->puede_generar_sp = $ordenCompra->puedeGenerarSolicitudPago();

        return $this->success($ordenCompra);
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
                'monto_disponible' => $ordenCompra->getMontoDisponible(),
            ],
            'solicitudes_pago' => $solicitudesPago,
        ]);
    }

    // === RUTAS DIRECTAS CON CONTEXTO DE PROVEEDOR ===

    /**
     * Muestra detalle de una orden de compra específica (sin contexto de proveedor)
     * Endpoint directo para el segmento gerente
     */
    public function showDirecto(Proveedor $proveedor,OrdenCompra $ordenCompra): JsonResponse
    {
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
     * Obtiene solicitudes de pago de una OC específica (sin contexto de proveedor)
     * Endpoint directo para el segmento gerente
     */
    public function getSolicitudesPagoDirecto(Proveedor $proveedor,OrdenCompra $ordenCompra): JsonResponse
    {
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
                'monto_disponible' => $ordenCompra->getMontoDisponible(),
                'proveedor' => $ordenCompra->proveedor->razon_social ?? 'N/A',
                'empresa' => $ordenCompra->empresaConstrucc->nombre ?? 'N/A'
            ],
            'solicitudes_pago' => $solicitudesPago
        ]);
    }

    /**
     * Listado general de órdenes de compra para el segmento gerente
     * Sin contexto de proveedor específico
     */
    public function indexGeneral(Proveedor $proveedor,Request $request): JsonResponse
    {
        $filters = $request->only(OrdenCompra::getFilters());
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 15);
        
        // Filtros adicionales para gerentes
        $proveedorId = $request->input('proveedor_id');
        $empresaId = $request->input('empresa_construcc_id');

        $query = OrdenCompra::query()
            ->with(['proveedor', 'empresaConstrucc', 'detalles'])
            ->filter($filters)
            ->orderBy($sortBy, $order);
            
        // Aplicar filtros específicos si se proporcionan
        if ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        }
        
        if ($empresaId) {
            $query->where('empresa_construcc_id', $empresaId);
        }

        // Agregar datos calculados
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
}
