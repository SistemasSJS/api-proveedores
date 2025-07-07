<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Linea;
use App\Models\Sucursal;
use App\Models\UnidadMedida;
use App\Models\TipoEmpresa;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Obtiene estadísticas completas del dashboard administrativo
     */
    public function getStatsCompletas(Request $request)
    {
        try {
            $stats = [
                'catalogos' => $this->getCatalogosStats(),
                'usuarios' => $this->getUsuariosStats(),
                'pedidos' => $this->getPedidosStats(),
                'metricas' => $this->calcularMetricasRendimiento(),
            ];

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Estadísticas obtenidas correctamente',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Obtiene métricas de rendimiento
     */
    public function getMetricasRendimiento(Request $request)
    {
        try {
            $metricas = $this->calcularMetricasRendimiento();

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Métricas obtenidas correctamente',
                'data' => $metricas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error al obtener métricas: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Obtiene resumen de catálogos
     */
    public function getCatalogosResumen(Request $request)
    {
        try {
            $catalogos = $this->getCatalogosStats();

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Resumen de catálogos obtenido correctamente',
                'data' => $catalogos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error al obtener resumen de catálogos: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Estadísticas de catálogos
     */
    private function getCatalogosStats()
    {
        return [
            'usuarios' => User::count(),
            'proveedores' => Proveedor::count(),
            'productos' => Producto::count(),
            'categorias' => Categoria::count(),
            'marcas' => Marca::count(),
            'sucursales' => Sucursal::count(),
            'lineas' => Linea::count(),
            'unidadesMedida' => UnidadMedida::count(),
            'tiposEmpresa' => TipoEmpresa::count(),
        ];
    }

    /**
     * Estadísticas de usuarios
     */
    private function getUsuariosStats()
    {
        $total = User::count();
        $activos = User::where('estado', 'activo')->count();

        return [
            'total' => $total,
            'administradores' => User::where('rol', 'ADMINISTRADOR')->count(),
            'proveedores' => User::where('rol', 'PROVEEDOR')->count(),
            'clientes' => User::where('rol', 'CLIENTE')->count(),
            'activos' => $activos,
            'inactivos' => $total - $activos,
        ];
    }

    /**
     * Estadísticas de pedidos
     */
    private function getPedidosStats()
    {
        $total = Pedido::count();
        $pendientes = Pedido::where('estado', 'pendiente')->count();
        $enProceso = Pedido::where('estado', 'en_proceso')->count();
        $cotizados = Pedido::where('estado', 'cotizado')->count();
        $entregados = Pedido::where('estado', 'entregado')->count();
        $cancelados = Pedido::where('estado', 'cancelado')->count();

        $montoTotal = Pedido::sum('monto_total') ?? 0;
        $montoPromedio = $total > 0 ? $montoTotal / $total : 0;

        return [
            'total' => $total,
            'pendientes' => $pendientes,
            'enProceso' => $enProceso,
            'cotizados' => $cotizados,
            'entregados' => $entregados,
            'cancelados' => $cancelados,
            'montoTotal' => $montoTotal,
            'montoPromedio' => $montoPromedio,
        ];
    }

    private function calcularMetricasRendimiento(): array
    {
        return [
            'visitasHoy' => $this->getVisitasHoy(),
            'visitasSemana' => $this->getVisitasSemana(),
            'visitasMes' => $this->getVisitasMes(),
            'conversionRate' => $this->getConversionRate(),
            'tiempoPromedioRespuesta' => $this->getTiempoPromedioRespuesta(),
            'satisfaccionCliente' => $this->getSatisfaccionCliente(),
            'productosPopulares' => $this->getProductosPopulares(),
            'ventasPorMes' => $this->getVentasPorMes(),
        ];
    }
    /**
     * Obtiene visitas de hoy
     */
    private function getVisitasHoy()
    {
        // Simulación - en producción esto vendría de analytics
        return rand(150, 300);
    }

    /**
     * Obtiene visitas de la semana
     */
    private function getVisitasSemana()
    {
        // Simulación - en producción esto vendría de analytics
        return rand(1000, 2000);
    }

    /**
     * Obtiene visitas del mes
     */
    private function getVisitasMes()
    {
        // Simulación - en producción esto vendría de analytics
        return rand(5000, 10000);
    }

    /**
     * Calcula tasa de conversión
     */
    private function getConversionRate()
    {
        $totalPedidos = Pedido::count();
        $pedidosEntregados = Pedido::where('estado', 'entregado')->count();

        if ($totalPedidos === 0) {
            return 0;
        }

        return round(($pedidosEntregados / $totalPedidos) * 100, 2);
    }

    /**
     * Obtiene tiempo promedio de respuesta en horas
     */
    private function getTiempoPromedioRespuesta()
    {
        // Simulación - en producción calcular desde created_at hasta primer cambio de estado
        return rand(2, 8);
    }

    /**
     * Obtiene satisfacción del cliente
     */
    private function getSatisfaccionCliente()
    {
        // Simulación - en producción esto vendría de encuestas/reviews
        return round(rand(40, 50) / 10, 1); // Entre 4.0 y 5.0
    }

    /**
     * Obtiene productos más populares
     */
    private function getProductosPopulares()
    {
        // En producción esto vendría de pedido_detalles
        return collect([
            [
                'id' => 1,
                'nombre' => 'Cemento Portland',
                'categoria' => 'Materiales de Construcción',
                'totalPedidos' => 45,
                'montoTotal' => 125000,
            ],
            [
                'id' => 2,
                'nombre' => 'Varilla de Acero #4',
                'categoria' => 'Aceros',
                'totalPedidos' => 38,
                'montoTotal' => 98000,
            ],
            [
                'id' => 3,
                'nombre' => 'Block de Concreto',
                'categoria' => 'Bloques',
                'totalPedidos' => 32,
                'montoTotal' => 76000,
            ],
            [
                'id' => 4,
                'nombre' => 'Arena de Río',
                'categoria' => 'Agregados',
                'totalPedidos' => 28,
                'montoTotal' => 45000,
            ],
            [
                'id' => 5,
                'nombre' => 'Grava Triturada',
                'categoria' => 'Agregados',
                'totalPedidos' => 25,
                'montoTotal' => 38000,
            ],
        ]);
    }

    /**
     * Obtiene ventas por mes (últimos 6 meses)
     */
    private function getVentasPorMes()
    {
        $ventas = collect();

        for ($i = 5; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonths($i);
            $mes = $fecha->format('M');

            // En producción esto sería: 
            // $ventas = Pedido::whereYear('created_at', $fecha->year)
            //     ->whereMonth('created_at', $fecha->month)
            //     ->sum('monto_total');

            $ventasSimuladas = rand(80000, 200000);
            $montoSimulado = rand(2000000, 5000000);

            $ventas->push([
                'mes' => $mes,
                'ventas' => $ventasSimuladas,
                'monto' => $montoSimulado,
            ]);
        }

        return $ventas;
    }
}
