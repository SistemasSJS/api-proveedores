<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Requisicion;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Estadísticas completas para administradores
     */
    public function getStatsCompletas(Request $request): JsonResponse
    {
        // Estadísticas generales del sistema
        $stats = [
            'usuarios' => [
                'total' => User::count(),
                'activos' => User::whereNotNull('email_verified_at')->count(),
                'nuevos_este_mes' => User::whereMonth('created_at', now()->month)->count(),
                'por_rol' => User::join('roles', 'users.role_id', '=', 'roles.id')
                    ->select('roles.name', DB::raw('count(*) as cantidad'))
                    ->groupBy('roles.name')
                    ->get()
                    ->pluck('cantidad', 'name'),
            ],
            'proveedores' => [
                'total' => Proveedor::count(),
                'activos' => Proveedor::where('estatus', 'activo')->count(),
                'pendientes' => Proveedor::where('estatus', 'pendiente')->count(),
                'con_productos' => Proveedor::has('productos')->count(),
                'con_sucursales' => Proveedor::has('sucursales')->count(),
            ],
            'productos' => [
                'total' => Producto::count(),
                'activos' => Producto::where('activo', true)->count(),
                'con_stock' => Producto::where('stock', '>', 0)->count(),
                'sin_stock' => Producto::where('stock', '=', 0)->count(),
                'por_categoria' => Producto::join('categorias', 'productos.categoria_id', '=', 'categorias.id')
                    ->select('categorias.nombre', DB::raw('count(*) as cantidad'))
                    ->groupBy('categorias.nombre')
                    ->orderBy('cantidad', 'desc')
                    ->limit(10)
                    ->get(),
            ],
            'requisiciones' => [
                'total' => Requisicion::count(),
                'este_mes' => Requisicion::whereMonth('created_at', now()->month)->count(),
                'pendientes' => Requisicion::where('estatus', 'pendiente')->count(),
                'en_proceso' => Requisicion::where('estatus', 'en_proceso')->count(),
                'completadas' => Requisicion::where('estatus', 'entregada')->count(),
                'por_estatus' => Requisicion::select('estatus', DB::raw('count(*) as cantidad'))
                    ->groupBy('estatus')
                    ->get()
                    ->pluck('cantidad', 'estatus'),
            ],
            'sucursales' => [
                'total' => Sucursal::count(),
                'activas' => Sucursal::where('activa', true)->count(),
                'promedio_por_proveedor' => Proveedor::withCount('sucursales')->avg('sucursales_count'),
            ],
        ];

        // Crecimiento mensual (últimos 12 meses)
        $crecimientoMensual = collect();
        for ($i = 11; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $mes = $fecha->format('M Y');

            $crecimientoMensual->push([
                'mes' => $mes,
                'usuarios' => User::whereYear('created_at', $fecha->year)
                    ->whereMonth('created_at', $fecha->month)->count(),
                'proveedores' => Proveedor::whereYear('created_at', $fecha->year)
                    ->whereMonth('created_at', $fecha->month)->count(),
                'requisiciones' => Requisicion::whereYear('created_at', $fecha->year)
                    ->whereMonth('created_at', $fecha->month)->count(),
                'productos' => Producto::whereYear('created_at', $fecha->year)
                    ->whereMonth('created_at', $fecha->month)->count(),
            ]);
        }

        // Top proveedores por actividad
        $topProveedores = Proveedor::withCount(['requisiciones', 'productos'])
            ->orderBy('requisiciones_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($proveedor) {
                return [
                    'id' => $proveedor->id,
                    'nombre' => $proveedor->nombre_comercial,
                    'requisiciones' => $proveedor->requisiciones_count,
                    'productos' => $proveedor->productos_count,
                    'estatus' => $proveedor->estatus,
                ];
            });

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

        // Análisis de tiempo de respuesta de proveedores
        $tiempoRespuesta = DB::table('requisiciones')
            ->join('proveedores', 'requisiciones.proveedor_id', '=', 'proveedores.id')
            ->where('requisiciones.estatus', '!=', 'pendiente')
            ->select(
                'proveedores.nombre_comercial',
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, requisiciones.created_at, requisiciones.updated_at)) as horas_promedio')
            )
            ->groupBy('proveedores.id', 'proveedores.nombre_comercial')
            ->orderBy('horas_promedio', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats_generales' => $stats,
                'crecimiento_mensual' => $crecimientoMensual,
                'top_proveedores' => $topProveedores,
                'productos_mas_solicitados' => $productosMasSolicitados,
                'tiempo_respuesta_proveedores' => $tiempoRespuesta,
                'fecha_actualizacion' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Métricas de rendimiento del sistema
     */
    public function getMetricasRendimiento(Request $request): JsonResponse
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
                'productos_solicitados' => Producto::whereHas('requisicionDetalles.requisicion', function ($query) use ($fechaInicio) {
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
                'productos_por_requisicion' => DB::table('requisicion_detalles')
                    ->join('requisiciones', 'requisicion_detalles.requisicion_id', '=', 'requisiciones.id')
                    ->where('requisiciones.created_at', '>=', $fechaInicio)
                    ->selectRaw('requisiciones.id, COUNT(requisicion_detalles.id) as productos')
                    ->groupBy('requisiciones.id')
                    ->avg('productos'),
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

    /**
     * Calcular tasa de conversión entre estados
     */
    private function calcularTasaConversion(string $estadoInicial, string $estadoFinal, $fechaInicio): float
    {
        $iniciales = Requisicion::where('estatus', $estadoInicial)
            ->where('created_at', '>=', $fechaInicio)
            ->count();

        if ($iniciales === 0) {
            return 0;
        }

        $convertidas = Requisicion::where('estatus', $estadoFinal)
            ->where('created_at', '>=', $fechaInicio)
            ->count();

        return round(($convertidas / $iniciales) * 100, 2);
    }

    /**
     * Calcular tasa de cancelación
     */
    private function calcularTasaCancelacion($fechaInicio): float
    {
        $total = Requisicion::where('created_at', '>=', $fechaInicio)->count();

        if ($total === 0) {
            return 0;
        }

        $canceladas = Requisicion::where('estatus', 'cancelada')
            ->where('created_at', '>=', $fechaInicio)
            ->count();

        return round(($canceladas / $total) * 100, 2);
    }
}
