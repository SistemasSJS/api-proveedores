<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $dashboardService;
    protected $reporteService;

    public function __construct(
        DashboardService $dashboardService,
        ReporteService $reporteService
    ) {
        $this->dashboardService = $dashboardService;
        $this->reporteService = $reporteService;
    }

    public function getStats(Request $request)
    {
        $user = Auth::user();

        if ($user->role->name === 'ADMINISTRADOR') {
            $stats = $this->dashboardService->getStatsAdmin();
            $crecimiento = $this->dashboardService->getCrecimientoMensual();
            $topProveedores = $this->dashboardService->getTopProveedores();

            return response()->json([
                'success' => true,
                'data' => [
                    'tipo' => 'admin',
                    'stats' => $stats,
                    'crecimiento_mensual' => $crecimiento,
                    'top_proveedores' => $topProveedores,
                ]
            ]);
        } elseif ($user->proveedores()->exists()) {
            $proveedorId = $user->proveedores()->first()->id;
            $stats = $this->dashboardService->getStatsProveedor($proveedorId);
            $estadisticasGenerales = $this->reporteService->reporteEstadisticasGenerales($proveedorId);

            return response()->json([
                'success' => true,
                'data' => [
                    'tipo' => 'proveedor',
                    'stats' => $stats,
                    'estadisticas_generales' => $estadisticasGenerales,
                ]
            ]);
        } else {
            $stats = $this->dashboardService->getStatsCliente($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'tipo' => 'cliente',
                    'stats' => $stats,
                ]
            ]);
        }
    }
}

class ClienteDashboardController extends Controller
{
    protected $dashboardService;
    protected $reporteService;

    public function __construct(
        DashboardService $dashboardService,
        ReporteService $reporteService
    ) {
        $this->dashboardService = $dashboardService;
        $this->reporteService = $reporteService;
    }

    public function getStats(Request $request)
    {
        $user = Auth::user();
        $stats = $this->dashboardService->getStatsCliente($user->id);

        // Requisiciones recientes
        $requisicionesRecientes = $user->requisiciones()
            ->with(['proveedor', 'detalles.producto'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($requisicion) {
                return [
                    'id' => $requisicion->id,
                    'numero_requisicion' => $requisicion->numero_requisicion,
                    'proveedor' => $requisicion->proveedor->nombre_comercial,
                    'estatus' => $requisicion->estatus,
                    'total_estimado' => $requisicion->total_estimado,
                    'fecha_requerida' => $requisicion->fecha_requerida,
                    'productos_count' => $requisicion->detalles->count(),
                    'created_at' => $requisicion->created_at,
                ];
            });

        // Notificaciones recientes
        $notificacionesRecientes = $user->notificaciones()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($notificacion) {
                return [
                    'id' => $notificacion->id,
                    'tipo' => $notificacion->tipo,
                    'titulo' => $notificacion->titulo,
                    'mensaje' => $notificacion->mensaje,
                    'leida' => $notificacion->leida,
                    'created_at' => $notificacion->created_at,
                ];
            });

        // Gráfico de requisiciones por mes
        $requisicionesPorMes = collect();
        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $count = $user->requisiciones()
                ->whereYear('created_at', $fecha->year)
                ->whereMonth('created_at', $fecha->month)
                ->count();

            $requisicionesPorMes->push([
                'mes' => $fecha->format('M Y'),
                'cantidad' => $count,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'requisiciones_recientes' => $requisicionesRecientes,
                'notificaciones_recientes' => $notificacionesRecientes,
                'requisiciones_por_mes' => $requisicionesPorMes,
            ],
        ]);
    }

    public function getResumenGastos(Request $request)
    {
        $request->validate([
            'periodo' => 'nullable|in:semana,mes,trimestre,año',
            'año' => 'nullable|integer|min:2020|max:' . (date('Y') + 1),
        ]);

        $user = Auth::user();
        $periodo = $request->input('periodo', 'mes');

        $fechaInicio = match ($periodo) {
            'semana' => now()->startOfWeek(),
            'mes' => now()->startOfMonth(),
            'trimestre' => now()->startOfQuarter(),
            'año' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $requisiciones = $user->requisiciones()
            ->where('created_at', '>=', $fechaInicio)
            ->whereIn('estatus', ['cotizada', 'entregada'])
            ->with(['proveedor', 'detalles.producto'])
            ->get();

        $resumen = [
            'periodo' => $periodo,
            'total_gastado' => $requisiciones->sum('total_estimado'),
            'promedio_por_requisicion' => $requisiciones->count() > 0
                ? $requisiciones->sum('total_estimado') / $requisiciones->count()
                : 0,
            'requisiciones_completadas' => $requisiciones->count(),
            'productos_diferentes' => $requisiciones->flatMap->detalles->pluck('producto_id')->unique()->count(),
            'proveedor_mas_usado' => $requisiciones->groupBy('proveedor_id')
                ->map(function ($grupo) {
                    return [
                        'proveedor' => $grupo->first()->proveedor->nombre_comercial,
                        'cantidad' => $grupo->count(),
                        'total' => $grupo->sum('total_estimado'),
                    ];
                })
                ->sortByDesc('total')
                ->first(),
        ];

        return response()->json(['success' => true, 'data' => $resumen]);
    }
}

class AdminDashboardController extends Controller
{
    protected $dashboardService;
    protected $reporteService;

    public function __construct(
        DashboardService $dashboardService,
        ReporteService $reporteService
    ) {
        $this->dashboardService = $dashboardService;
        $this->reporteService = $reporteService;
    }

    public function getStatsCompletas(Request $request)
    {
        $stats = $this->dashboardService->getStatsAdmin();
        $crecimiento = $this->dashboardService->getCrecimientoMensual();
        $topProveedores = $this->dashboardService->getTopProveedores();

        // Productos más solicitados globalmente
        $productosMasSolicitados = DB::table('requisicion_detalles')
            ->join('productos', 'requisicion_detalles.producto_id', '=', 'productos.id')
            ->join('proveedores', 'productos.proveedor_id', '=', 'proveedores.id')
            ->select(
                'productos.id',
                'productos.nombre',
                'productos.sku',
                'proveedores.nombre_comercial as proveedor',
                DB::raw('SUM(requisicion_detalles.cantidad) as total_solicitado'),
                DB::raw('COUNT(DISTINCT requisicion_detalles.requisicion_id) as veces_solicitado')
            )
            ->groupBy('productos.id', 'productos.nombre', 'productos.sku', 'proveedores.nombre_comercial')
            ->orderBy('total_solicitado', 'desc')
            ->limit(15)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats_generales' => $stats,
                'crecimiento_mensual' => $crecimiento,
                'top_proveedores' => $topProveedores,
                'productos_mas_solicitados' => $productosMasSolicitados,
                'fecha_actualizacion' => now()->toISOString(),
            ],
        ]);
    }

    public function getMetricasRendimiento(Request $request)
    {
        $request->validate([
            'dias' => 'nullable|integer|min:1|max:90',
        ]);

        $dias = $request->input('dias', 30);
        $fechaInicio = now()->subDays($dias);

        $metricas = [
            'actividad_usuarios' => [
                'usuarios_activos' => User::whereHas('requisiciones', function ($query) use ($fechaInicio) {
                    $query->where('created_at', '>=', $fechaInicio);
                })->count(),
                'proveedores_activos' => Proveedor::whereHas('requisiciones', function ($query) use ($fechaInicio) {
                    $query->where('created_at', '>=', $fechaInicio);
                })->count(),
            ],
            'conversion' => [
                'requisiciones_a_cotizadas' => $this->calcularTasaConversion('pendiente', 'cotizada', $fechaInicio),
                'cotizadas_a_entregadas' => $this->calcularTasaConversion('cotizada', 'entregada', $fechaInicio),
                'tasa_cancelacion' => $this->calcularTasaCancelacion($fechaInicio),
            ],
            'volumenes' => [
                'requisiciones_por_dia' => Requisicion::where('created_at', '>=', $fechaInicio)
                    ->selectRaw('DATE(created_at) as fecha, COUNT(*) as cantidad')
                    ->groupBy('fecha')
                    ->orderBy('fecha')
                    ->get(),
                'valor_promedio_requisicion' => Requisicion::where('created_at', '>=', $fechaInicio)
                    ->avg('total_estimado'),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'periodo_dias' => $dias,
                'fecha_inicio' => $fechaInicio->toDateString(),
                'metricas' => $metricas,
            ],
        ]);
    }

    private function calcularTasaConversion(string $estadoInicial, string $estadoFinal, $fechaInicio): float
    {
        $iniciales = Requisicion::where('estatus', $estadoInicial)
            ->where('created_at', '>=', $fechaInicio)
            ->count();

        if ($iniciales === 0) return 0;

        $convertidas = Requisicion::where('estatus', $estadoFinal)
            ->where('created_at', '>=', $fechaInicio)
            ->count();

        return round(($convertidas / $iniciales) * 100, 2);
    }

    private function calcularTasaCancelacion($fechaInicio): float
    {
        $total = Requisicion::where('created_at', '>=', $fechaInicio)->count();
        if ($total === 0) return 0;

        $canceladas = Requisicion::where('estatus', 'cancelada')
            ->where('created_at', '>=', $fechaInicio)
            ->count();

        return round(($canceladas / $total) * 100, 2);
    }
}
