<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\Cotizacion;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;

class PedidoService
{
    /**
     * Crear pedido desde cotización
     */
    public function crearDesdeCotizacion(Cotizacion $cotizacion, array $data): Pedido
    {
        return DB::transaction(function () use ($cotizacion, $data) {
            // Crear el pedido
            $pedido = Pedido::create([
                'requisicion_id' => $cotizacion->requisicion_id,
                'cotizacion_id' => $cotizacion->id,
                'fecha_confirmacion' => now(),
                'fecha_entrega_estimada' => $data['fecha_entrega_estimada'],
                'observaciones' => $data['observaciones'] ?? null,
                'estatus' => 'confirmado'
            ]);

            // Crear los detalles del pedido
            foreach ($data['detalles'] as $detalleData) {
                $cotizacionDetalle = $cotizacion->detalles()
                    ->findOrFail($detalleData['cotizacion_detalle_id']);

                PedidoDetalle::create([
                    'pedido_id' => $pedido->id,
                    'cotizacion_detalle_id' => $cotizacionDetalle->id,
                    'cantidad_confirmada' => $detalleData['cantidad_confirmada'],
                    'precio_unitario_final' => $detalleData['precio_unitario_final'],
                    'descuento_unitario' => $detalleData['descuento_unitario'] ?? 0,
                    'observaciones' => $detalleData['observaciones'] ?? null,
                ]);
            }

            // Calcular totales
            $pedido->calcularTotales();

            return $pedido->load(['detalles', 'requisicion', 'cotizacion']);
        });
    }

    /**
     * Actualizar estatus del pedido
     */
    public function actualizarEstatus(Pedido $pedido, string $nuevoEstatus, array $data = []): bool
    {
        if (!$pedido->puedeActualizarEstatus($nuevoEstatus)) {
            return false;
        }

        $updateData = ['estatus' => $nuevoEstatus];

        // Agregar datos específicos según el estatus
        switch ($nuevoEstatus) {
            case 'en_preparacion':
                $updateData['fecha_inicio_preparacion'] = now();
                break;
            
            case 'listo_para_entrega':
                $updateData['fecha_listo_entrega'] = now();
                break;
            
            case 'en_transito':
                $updateData['numero_guia'] = $data['numero_guia'] ?? null;
                $updateData['transportista'] = $data['transportista'] ?? null;
                $updateData['fecha_envio'] = $data['fecha_envio'] ?? now();
                break;
            
            case 'entregado':
                $updateData['fecha_entrega_real'] = $data['fecha_entrega_real'] ?? now();
                $updateData['observaciones_entrega'] = $data['observaciones_entrega'] ?? null;
                break;
            
            case 'facturado':
                $updateData['fecha_facturacion'] = now();
                break;
            
            case 'cancelado':
                $updateData['fecha_cancelacion'] = now();
                $updateData['motivo_cancelacion'] = $data['motivo_cancelacion'] ?? null;
                break;
        }

        // Agregar observaciones si se proporcionan
        if (isset($data['observaciones'])) {
            $updateData['observaciones'] = $data['observaciones'];
        }

        $pedido->update($updateData);
        return true;
    }

    /**
     * Cancelar pedido
     */
    public function cancelar(Pedido $pedido, string $motivo): bool
    {
        if (!$pedido->puedeActualizarEstatus('cancelado')) {
            return false;
        }

        $pedido->update([
            'estatus' => 'cancelado',
            'fecha_cancelacion' => now(),
            'motivo_cancelacion' => $motivo
        ]);

        return true;
    }

    /**
     * Duplicar pedido
     */
    public function duplicar(Pedido $pedidoOriginal): Pedido
    {
        return DB::transaction(function () use ($pedidoOriginal) {
            // Crear nuevo pedido basado en el original
            $nuevoPedido = Pedido::create([
                'requisicion_id' => $pedidoOriginal->requisicion_id,
                'cotizacion_id' => $pedidoOriginal->cotizacion_id,
                'fecha_confirmacion' => now(),
                'fecha_entrega_estimada' => now()->addDays(7),
                'observaciones' => 'Duplicado del pedido #' . $pedidoOriginal->numero_pedido,
                'estatus' => 'confirmado'
            ]);

            // Duplicar detalles
            foreach ($pedidoOriginal->detalles as $detalle) {
                PedidoDetalle::create([
                    'pedido_id' => $nuevoPedido->id,
                    'cotizacion_detalle_id' => $detalle->cotizacion_detalle_id,
                    'cantidad_confirmada' => $detalle->cantidad_confirmada,
                    'precio_unitario_final' => $detalle->precio_unitario_final,
                    'descuento_unitario' => $detalle->descuento_unitario,
                    'observaciones' => $detalle->observaciones,
                ]);
            }

            // Calcular totales
            $nuevoPedido->calcularTotales();

            return $nuevoPedido->load(['detalles', 'requisicion', 'cotizacion']);
        });
    }

    /**
     * Obtener estadísticas de pedidos para un usuario
     */
    public function getEstadisticasParaUsuario(int $usuarioId): array
    {
        $pedidos = Pedido::whereHas('requisicion', function ($query) use ($usuarioId) {
            $query->where('usuario_id', $usuarioId);
        });

        return [
            'total' => $pedidos->count(),
            'confirmados' => $pedidos->where('estatus', 'confirmado')->count(),
            'en_preparacion' => $pedidos->where('estatus', 'en_preparacion')->count(),
            'listos_para_entrega' => $pedidos->where('estatus', 'listo_para_entrega')->count(),
            'en_transito' => $pedidos->where('estatus', 'en_transito')->count(),
            'entregados' => $pedidos->where('estatus', 'entregado')->count(),
            'facturados' => $pedidos->where('estatus', 'facturado')->count(),
            'cancelados' => $pedidos->where('estatus', 'cancelado')->count(),
            'este_mes' => $pedidos->whereMonth('fecha_confirmacion', now()->month)->count(),
            'total_gastado' => $pedidos->whereIn('estatus', ['entregado', 'facturado'])->sum('total'),
            'promedio_por_pedido' => $pedidos->whereIn('estatus', ['entregado', 'facturado'])->avg('total'),
            'vencidos' => $pedidos->where('fecha_entrega_estimada', '<', now()->toDateString())
                ->whereNotIn('estatus', ['entregado', 'facturado', 'cancelado'])
                ->count(),
            'proximos_vencer' => $pedidos->whereBetween('fecha_entrega_estimada', [
                now()->toDateString(),
                now()->addDays(7)->toDateString()
            ])->whereNotIn('estatus', ['entregado', 'facturado', 'cancelado'])
            ->count(),
        ];
    }

    /**
     * Obtener estadísticas de pedidos para un proveedor
     */
    public function getEstadisticasParaProveedor(int $proveedorId): array
    {
        $pedidos = Pedido::whereHas('requisicion', function ($query) use ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        });

        $stats = [
            'total' => $pedidos->count(),
            'confirmados' => $pedidos->where('estatus', 'confirmado')->count(),
            'en_preparacion' => $pedidos->where('estatus', 'en_preparacion')->count(),
            'listos_para_entrega' => $pedidos->where('estatus', 'listo_para_entrega')->count(),
            'en_transito' => $pedidos->where('estatus', 'en_transito')->count(),
            'entregados' => $pedidos->where('estatus', 'entregado')->count(),
            'facturados' => $pedidos->where('estatus', 'facturado')->count(),
            'cancelados' => $pedidos->where('estatus', 'cancelado')->count(),
            'este_mes' => $pedidos->whereMonth('fecha_confirmacion', now()->month)->count(),
            'total_vendido' => $pedidos->whereIn('estatus', ['entregado', 'facturado'])->sum('total'),
            'promedio_por_pedido' => $pedidos->whereIn('estatus', ['entregado', 'facturado'])->avg('total'),
            'vencidos' => $pedidos->where('fecha_entrega_estimada', '<', now()->toDateString())
                ->whereNotIn('estatus', ['entregado', 'facturado', 'cancelado'])
                ->count(),
            'proximos_vencer' => $pedidos->whereBetween('fecha_entrega_estimada', [
                now()->toDateString(),
                now()->addDays(7)->toDateString()
            ])->whereNotIn('estatus', ['entregado', 'facturado', 'cancelado'])
            ->count(),
        ];

        // Estadísticas adicionales para proveedor
        $stats['tiempo_promedio_preparacion'] = $this->calcularTiempoPromedioPreparacion($proveedorId);
        $stats['tasa_entrega_puntual'] = $this->calcularTasaEntregaPuntual($proveedorId);
        $stats['productos_mas_pedidos'] = $this->obtenerProductosMasPedidos($proveedorId);

        return $stats;
    }

    /**
     * Calcular tiempo promedio de preparación del proveedor
     */
    private function calcularTiempoPromedioPreparacion(int $proveedorId): float
    {
        $pedidos = Pedido::whereHas('requisicion', function ($query) use ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        })
        ->whereNotNull('fecha_inicio_preparacion')
        ->whereNotNull('fecha_listo_entrega')
        ->get();

        if ($pedidos->isEmpty()) {
            return 0;
        }

        $tiempos = $pedidos->map(function ($pedido) {
            return $pedido->fecha_inicio_preparacion->diffInHours($pedido->fecha_listo_entrega);
        });

        return round($tiempos->avg(), 2);
    }

    /**
     * Calcular tasa de entrega puntual del proveedor
     */
    private function calcularTasaEntregaPuntual(int $proveedorId): float
    {
        $pedidosEntregados = Pedido::whereHas('requisicion', function ($query) use ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        })
        ->where('estatus', 'entregado')
        ->whereNotNull('fecha_entrega_real');

        $total = $pedidosEntregados->count();

        if ($total === 0) {
            return 0;
        }

        $puntuales = $pedidosEntregados->whereRaw('DATE(fecha_entrega_real) <= fecha_entrega_estimada')
            ->count();

        return round(($puntuales / $total) * 100, 2);
    }

    /**
     * Obtener productos más pedidos de un proveedor
     */
    private function obtenerProductosMasPedidos(int $proveedorId, int $limite = 10): array
    {
        $productos = DB::table('pedido_detalles')
            ->join('pedidos', 'pedido_detalles.pedido_id', '=', 'pedidos.id')
            ->join('requisiciones', 'pedidos.requisicion_id', '=', 'requisiciones.id')
            ->join('cotizacion_detalles', 'pedido_detalles.cotizacion_detalle_id', '=', 'cotizacion_detalles.id')
            ->join('requisicion_detalles', 'cotizacion_detalles.requisicion_detalle_id', '=', 'requisicion_detalles.id')
            ->join('productos', 'requisicion_detalles.producto_id', '=', 'productos.id')
            ->where('requisiciones.proveedor_id', $proveedorId)
            ->whereIn('pedidos.estatus', ['entregado', 'facturado'])
            ->select([
                'productos.id',
                'productos.nombre',
                'productos.sku',
                DB::raw('SUM(pedido_detalles.cantidad_confirmada) as total_pedido'),
                DB::raw('COUNT(DISTINCT pedidos.id) as veces_pedido'),
                DB::raw('AVG(pedido_detalles.precio_unitario_final) as precio_promedio')
            ])
            ->groupBy('productos.id', 'productos.nombre', 'productos.sku')
            ->orderBy('total_pedido', 'desc')
            ->limit($limite)
            ->get()
            ->toArray();

        return $productos;
    }

    /**
     * Obtener resumen de ventas mensuales
     */
    public function getVentasMensuales(int $proveedorId, int $meses = 12): array
    {
        $ventas = DB::table('pedidos')
            ->join('requisiciones', 'pedidos.requisicion_id', '=', 'requisiciones.id')
            ->where('requisiciones.proveedor_id', $proveedorId)
            ->whereIn('pedidos.estatus', ['entregado', 'facturado'])
            ->where('pedidos.fecha_confirmacion', '>=', now()->subMonths($meses))
            ->select([
                DB::raw('DATE_FORMAT(pedidos.fecha_confirmacion, "%Y-%m") as mes'),
                DB::raw('COUNT(*) as total_pedidos'),
                DB::raw('SUM(pedidos.total) as monto_total'),
                DB::raw('AVG(pedidos.total) as monto_promedio')
            ])
            ->groupBy('mes')
            ->orderBy('mes', 'desc')
            ->get()
            ->toArray();

        return $ventas;
    }

    /**
     * Obtener alertas de pedidos
     */
    public function getAlertasPedidos(int $proveedorId): array
    {
        $alertas = [];

        // Pedidos vencidos
        $vencidos = Pedido::whereHas('requisicion', function ($query) use ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        })
        ->where('fecha_entrega_estimada', '<', now()->toDateString())
        ->whereNotIn('estatus', ['entregado', 'facturado', 'cancelado'])
        ->count();

        if ($vencidos > 0) {
            $alertas[] = [
                'tipo' => 'vencidos',
                'cantidad' => $vencidos,
                'mensaje' => "Tienes {$vencidos} pedidos vencidos",
                'prioridad' => 'alta'
            ];
        }

        // Pedidos próximos a vencer
        $proximosVencer = Pedido::whereHas('requisicion', function ($query) use ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        })
        ->whereBetween('fecha_entrega_estimada', [
            now()->toDateString(),
            now()->addDays(3)->toDateString()
        ])
        ->whereNotIn('estatus', ['entregado', 'facturado', 'cancelado'])
        ->count();

        if ($proximosVencer > 0) {
            $alertas[] = [
                'tipo' => 'proximos_vencer',
                'cantidad' => $proximosVencer,
                'mensaje' => "Tienes {$proximosVencer} pedidos próximos a vencer",
                'prioridad' => 'media'
            ];
        }

        // Pedidos en preparación por mucho tiempo
        $preparacionLenta = Pedido::whereHas('requisicion', function ($query) use ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        })
        ->where('estatus', 'en_preparacion')
        ->where('fecha_inicio_preparacion', '<', now()->subDays(5))
        ->count();

        if ($preparacionLenta > 0) {
            $alertas[] = [
                'tipo' => 'preparacion_lenta',
                'cantidad' => $preparacionLenta,
                'mensaje' => "Tienes {$preparacionLenta} pedidos en preparación por más de 5 días",
                'prioridad' => 'media'
            ];
        }

        return $alertas;
    }
}