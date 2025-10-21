<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Pedido;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminPedidosController extends Controller
{
    /**
     * Lista todos los pedidos con filtros
     */
    public function adminIndex(Request $request)
    {
        try {
            $query = Pedido::with(['cliente', 'proveedor']);

            // Aplicar filtros
            if ($request->filled('busqueda')) {
                $busqueda = $request->input('busqueda');
                $query->where(function ($q) use ($busqueda) {
                    $q->where('numero_orden', 'LIKE', "%{$busqueda}%")
                        ->orWhereHas('cliente', function ($clienteQuery) use ($busqueda) {
                            $clienteQuery->where('nombre', 'LIKE', "%{$busqueda}%");
                        })
                        ->orWhereHas('proveedor', function ($proveedorQuery) use ($busqueda) {
                            $proveedorQuery->where('nombre_comercial', 'LIKE', "%{$busqueda}%");
                        });
                });
            }

            if ($request->filled('estado')) {
                $query->where('estado', $request->input('estado'));
            }

            if ($request->filled('proveedor')) {
                $query->where('proveedor_id', $request->input('proveedor'));
            }

            if ($request->filled('fechaInicio')) {
                $query->whereDate('created_at', '>=', $request->input('fechaInicio'));
            }

            if ($request->filled('fechaFin')) {
                $query->whereDate('created_at', '<=', $request->input('fechaFin'));
            }

            // Ordenamiento
            $query->orderBy('created_at', 'desc');

            // Paginación
            $limit = $request->input('limit', 25);
            $pedidos = $query->paginate($limit);

            // Formatear datos
            $pedidosFormatted = $pedidos->map(function ($pedido) {
                return [
                    'id' => $pedido->id,
                    'numeroOrden' => $pedido->numero_orden,
                    'cliente' => [
                        'id' => $pedido->cliente->id,
                        'nombre' => $pedido->cliente->nombre,
                        'email' => $pedido->cliente->email,
                    ],
                    'proveedor' => [
                        'id' => $pedido->proveedor->id,
                        'nombre' => $pedido->proveedor->nombre_comercial,
                    ],
                    'estado' => $pedido->estado,
                    'montoTotal' => $pedido->monto_total,
                    'productos' => $pedido->detalles()->count(),
                    'fechaCreacion' => $pedido->created_at->toDateString(),
                    'fechaEntrega' => $pedido->fecha_entrega ? $pedido->fecha_entrega->toDateString() : null,
                    'observaciones' => $pedido->observaciones,
                ];
            });

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Pedidos obtenidos correctamente',
                'data' => $pedidosFormatted,
                'pagination' => [
                    'current_page' => $pedidos->currentPage(),
                    'total_pages' => $pedidos->lastPage(),
                    'total_items' => $pedidos->total(),
                    'per_page' => $pedidos->perPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error al obtener pedidos: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de pedidos
     */
    public function adminStats(Request $request)
    {
        try {
            $total = Pedido::count();
            $pendientes = Pedido::where('estado', 'pendiente')->count();
            $enProceso = Pedido::where('estado', 'en_proceso')->count();
            $cotizados = Pedido::where('estado', 'cotizado')->count();
            $entregados = Pedido::where('estado', 'entregado')->count();
            $cancelados = Pedido::where('estado', 'cancelado')->count();

            $montoTotal = Pedido::sum('monto_total') ?? 0;
            $montoPromedio = $total > 0 ? $montoTotal / $total : 0;

            $stats = [
                'total' => $total,
                'pendientes' => $pendientes,
                'enProceso' => $enProceso,
                'cotizados' => $cotizados,
                'entregados' => $entregados,
                'cancelados' => $cancelados,
                'montoTotal' => $montoTotal,
                'montoPromedio' => $montoPromedio,
            ];

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Estadísticas obtenidas correctamente',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error al obtener estadísticas: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Obtiene un pedido específico
     */
    public function show($id)
    {
        try {
            $pedido = Pedido::with(['cliente', 'proveedor', 'detalles.producto'])->findOrFail($id);

            $pedidoData = [
                'id' => $pedido->id,
                'numeroOrden' => $pedido->numero_orden,
                'cliente' => [
                    'id' => $pedido->cliente->id,
                    'nombre' => $pedido->cliente->nombre,
                    'email' => $pedido->cliente->email,
                ],
                'proveedor' => [
                    'id' => $pedido->proveedor->id,
                    'nombre' => $pedido->proveedor->nombre_comercial,
                ],
                'estado' => $pedido->estado,
                'montoTotal' => $pedido->monto_total,
                'productos' => $pedido->detalles()->count(),
                'fechaCreacion' => $pedido->created_at->toDateString(),
                'fechaEntrega' => $pedido->fecha_entrega ? $pedido->fecha_entrega->toDateString() : null,
                'observaciones' => $pedido->observaciones,
            ];

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Pedido obtenido correctamente',
                'data' => $pedidoData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error al obtener pedido: '.$e->getMessage(),
                'data' => null,
            ], 404);
        }
    }

    /**
     * Fuerza el cambio de estado de un pedido
     */
    public function forceStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pendiente,en_proceso,cotizado,entregado,cancelado',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Estado no válido',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $pedido = Pedido::findOrFail($id);
            $estadoAnterior = $pedido->estado;
            $nuevoEstado = $request->input('status');

            $pedido->update([
                'estado' => $nuevoEstado,
                'fecha_actualizacion_estado' => now(),
            ]);

            // Registrar auditoría
            $this->registrarAuditoria($pedido, 'FORCE_STATUS', [
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado,
                'usuario_admin' => auth()->user()->nombre,
            ]);

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Estado del pedido actualizado correctamente',
                'data' => [
                    'id' => $pedido->id,
                    'estado' => $pedido->estado,
                    'numeroOrden' => $pedido->numero_orden,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error al cambiar estado: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Elimina un pedido
     */
    public function destroy($id)
    {
        try {
            $pedido = Pedido::findOrFail($id);

            // Registrar auditoría antes de eliminar
            $this->registrarAuditoria($pedido, 'DELETE', [
                'numero_orden' => $pedido->numero_orden,
                'estado' => $pedido->estado,
                'monto_total' => $pedido->monto_total,
                'usuario_admin' => auth()->user()->nombre,
            ]);

            $pedido->delete();

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Pedido eliminado correctamente',
                'data' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error al eliminar pedido: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Obtiene reportes avanzados
     */
    public function adminReports(Request $request)
    {
        try {
            $fechaInicio = $request->input('fechaInicio', Carbon::now()->subDays(30)->toDateString());
            $fechaFin = $request->input('fechaFin', Carbon::now()->toDateString());

            $reportes = [
                'resumen_periodo' => [
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'total_pedidos' => Pedido::whereBetween('created_at', [$fechaInicio, $fechaFin])->count(),
                    'monto_total' => Pedido::whereBetween('created_at', [$fechaInicio, $fechaFin])->sum('monto_total'),
                ],
                'por_estado' => Pedido::whereBetween('created_at', [$fechaInicio, $fechaFin])
                    ->selectRaw('estado, COUNT(*) as cantidad, SUM(monto_total) as monto')
                    ->groupBy('estado')
                    ->get(),
                'por_proveedor' => Pedido::with('proveedor')
                    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                    ->selectRaw('proveedor_id, COUNT(*) as cantidad, SUM(monto_total) as monto')
                    ->groupBy('proveedor_id')
                    ->orderBy('cantidad', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'proveedor' => $item->proveedor->nombre_comercial,
                            'cantidad' => $item->cantidad,
                            'monto' => $item->monto,
                        ];
                    }),
                'tendencia_diaria' => Pedido::whereBetween('created_at', [$fechaInicio, $fechaFin])
                    ->selectRaw('DATE(created_at) as fecha, COUNT(*) as cantidad, SUM(monto_total) as monto')
                    ->groupBy('fecha')
                    ->orderBy('fecha')
                    ->get(),
            ];

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Reportes obtenidos correctamente',
                'data' => $reportes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error al obtener reportes: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Obtiene log de auditoría de un pedido
     */
    public function auditLog($id)
    {
        try {
            $pedido = Pedido::findOrFail($id);

            // Obtener logs de auditoría relacionados al pedido
            $auditLogs = AuditLog::where('tabla', 'pedidos')
                ->where('registro_id', $id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'usuario' => $log->usuario,
                        'accion' => $log->accion,
                        'tabla' => $log->tabla,
                        'registroId' => $log->registro_id,
                        'valoresAnteriores' => $log->valores_anteriores,
                        'valoresNuevos' => $log->valores_nuevos,
                        'fecha' => $log->created_at->toDateTimeString(),
                        'ip' => $log->ip_address ?? 'N/A',
                    ];
                });

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Log de auditoría obtenido correctamente',
                'data' => $auditLogs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error al obtener log de auditoría: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Sincroniza con sistema de facturación
     */
    public function syncBilling($id)
    {
        try {
            $pedido = Pedido::findOrFail($id);

            // Simulación de sincronización con sistema externo
            // En producción aquí iría la lógica real de integración

            $pedido->update([
                'sincronizado_facturacion' => true,
                'fecha_sincronizacion' => now(),
            ]);

            $this->registrarAuditoria($pedido, 'SYNC_BILLING', [
                'usuario_admin' => auth()->user()->nombre,
                'fecha_sincronizacion' => now(),
            ]);

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Sincronización con facturación exitosa',
                'data' => [
                    'pedido_id' => $pedido->id,
                    'sincronizado' => true,
                    'fecha_sincronizacion' => $pedido->fecha_sincronizacion,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error en sincronización: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Genera factura automática
     */
    public function generateInvoice($id)
    {
        try {
            $pedido = Pedido::findOrFail($id);

            // Simulación de generación de factura
            // En producción aquí iría la lógica real de generación

            $numeroFactura = 'FACT-'.date('Ymd').'-'.str_pad($pedido->id, 6, '0', STR_PAD_LEFT);

            $pedido->update([
                'facturado' => true,
                'numero_factura' => $numeroFactura,
                'fecha_facturacion' => now(),
            ]);

            $this->registrarAuditoria($pedido, 'GENERATE_INVOICE', [
                'numero_factura' => $numeroFactura,
                'usuario_admin' => auth()->user()->nombre,
                'fecha_facturacion' => now(),
            ]);

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Factura generada correctamente',
                'data' => [
                    'pedido_id' => $pedido->id,
                    'numero_factura' => $numeroFactura,
                    'fecha_facturacion' => $pedido->fecha_facturacion,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error al generar factura: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Confirma el pago de un pedido
     */
    public function paymentConfirmed($id)
    {
        try {
            $pedido = Pedido::findOrFail($id);

            $pedido->update([
                'pago_confirmado' => true,
                'fecha_pago' => now(),
                'estado' => 'entregado', // Automáticamente marcar como entregado
            ]);

            $this->registrarAuditoria($pedido, 'PAYMENT_CONFIRMED', [
                'usuario_admin' => auth()->user()->nombre,
                'fecha_pago' => now(),
                'estado_actualizado' => 'entregado',
            ]);

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Pago confirmado correctamente',
                'data' => [
                    'pedido_id' => $pedido->id,
                    'pago_confirmado' => true,
                    'fecha_pago' => $pedido->fecha_pago,
                    'estado' => $pedido->estado,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error al confirmar pago: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Registra una entrada de auditoría
     */
    private function registrarAuditoria($pedido, $accion, $datos = [])
    {
        try {
            AuditLog::create([
                'usuario' => auth()->user()->nombre ?? 'Sistema',
                'accion' => $accion,
                'tabla' => 'pedidos',
                'registro_id' => $pedido->id,
                'valores_anteriores' => $pedido->getOriginal(),
                'valores_nuevos' => array_merge($pedido->toArray(), $datos),
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);
        } catch (\Exception $e) {
            // Log de error pero no interrumpir el flujo principal
            Log::error('Error al registrar auditoría: '.$e->getMessage());
        }
    }
}
