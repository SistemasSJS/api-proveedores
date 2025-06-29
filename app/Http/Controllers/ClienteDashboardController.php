<?php


namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Requisicion;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ClienteDashboardController extends Controller
{
    /**
     * Estadísticas del dashboard del cliente
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Estadísticas básicas
        $stats = [
            'requisiciones' => [
                'total' => $user->requisiciones()->count(),
                'pendientes' => $user->requisiciones()->where('estatus', 'pendiente')->count(),
                'en_proceso' => $user->requisiciones()->where('estatus', 'en_proceso')->count(),
                'cotizadas' => $user->requisiciones()->where('estatus', 'cotizada')->count(),
                'entregadas' => $user->requisiciones()->where('estatus', 'entregada')->count(),
                'canceladas' => $user->requisiciones()->where('estatus', 'cancelada')->count(),
                'este_mes' => $user->requisiciones()->whereMonth('created_at', now()->month)->count(),
            ],
            'notificaciones' => [
                'total' => $user->notificaciones()->count(),
                'no_leidas' => $user->notificaciones()->where('leida', false)->count(),
                'hoy' => $user->notificaciones()->whereDate('created_at', today())->count(),
            ],
            'actividad_reciente' => [
                'ultima_requisicion' => $user->requisiciones()->latest()->first()?->created_at,
                'proxima_entrega' => $user->requisiciones()
                    ->where('estatus', 'en_proceso')
                    ->orderBy('fecha_requerida', 'asc')
                    ->first()?->fecha_requerida,
            ]
        ];

        // Requisiciones recientes (últimas 5)
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

        // Notificaciones recientes (últimas 5)
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

        // Gráfico de requisiciones por mes (últimos 6 meses)
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

        // Proveedores más utilizados
        $proveedoresFrecuentes = $user->requisiciones()
            ->with('proveedor')
            ->get()
            ->groupBy('proveedor_id')
            ->map(function ($requisiciones, $proveedorId) {
                $proveedor = $requisiciones->first()->proveedor;
                return [
                    'id' => $proveedor->id,
                    'nombre' => $proveedor->nombre_comercial,
                    'cantidad_requisiciones' => $requisiciones->count(),
                    'total_gastado' => $requisiciones->sum('total_estimado'),
                ];
            })
            ->sortByDesc('cantidad_requisiciones')
            ->take(5)
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'requisiciones_recientes' => $requisicionesRecientes,
                'notificaciones_recientes' => $notificacionesRecientes,
                'requisiciones_por_mes' => $requisicionesPorMes,
                'proveedores_frecuentes' => $proveedoresFrecuentes,
            ],
        ]);
    }

    /**
     * Resumen de gastos del cliente
     */
    public function getResumenGastos(Request $request): JsonResponse
    {
        $request->validate([
            'periodo' => 'nullable|in:semana,mes,trimestre,año',
            'año' => 'nullable|integer|min:2020|max:' . (date('Y') + 1),
        ]);

        $user = Auth::user();
        $periodo = $request->input('periodo', 'mes');
        $año = $request->input('año', date('Y'));

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

        return response()->json([
            'success' => true,
            'data' => $resumen,
        ]);
    }
}
