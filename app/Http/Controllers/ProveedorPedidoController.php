<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pedido\PedidoUpdateRequest;
use App\Http\Resources\PedidoResource;
use App\Models\Pedido;
use App\Models\Proveedor;
use App\Services\NotificacionService;
use App\Services\PedidoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProveedorPedidoController extends Controller
{
    protected $pedidoService;

    public function __construct(PedidoService $pedidoService)
    {
        $this->pedidoService = $pedidoService;
    }

    /**
     * Listar pedidos del proveedor
     */
    public function index(Request $request, Proveedor $proveedor)
    {
        $this->authorize('viewProveedorPedidos', $proveedor);

        $query = $proveedor->pedidos()
            ->with(['requisicion.usuario', 'cotizacion', 'detalles.producto']);

        // Filtros
        if ($request->estatus) {
            $query->where('estatus', $request->estatus);
        }

        if ($request->fecha_desde) {
            $query->whereDate('fecha_confirmacion', '>=', $request->fecha_desde);
        }

        if ($request->fecha_hasta) {
            $query->whereDate('fecha_confirmacion', '<=', $request->fecha_hasta);
        }

        if ($request->numero_pedido) {
            $query->where('numero_pedido', 'like', '%'.$request->numero_pedido.'%');
        }

        if ($request->cliente) {
            $query->whereHas('requisicion.usuario', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->cliente.'%');
            });
        }

        if ($request->vencidos === 'true') {
            $query->vencidos();
        }

        if ($request->proximos_vencer) {
            $query->proximosAVencer($request->proximos_vencer);
        }

        // Ordenamiento
        $orderBy = $request->get('order_by', 'fecha_confirmacion');
        $orderDirection = $request->get('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        $pedidos = $query->paginate($request->get('per_page', 15));

        // Obtener estadísticas
        $stats = $this->pedidoService->getEstadisticasParaProveedor($proveedor->id);

        return response()->json([
            'success' => true,
            'data' => [
                'pedidos' => PedidoResource::collection($pedidos),
                'estadisticas' => $stats,
                'filtros_aplicados' => [
                    'estatus' => $request->estatus,
                    'fecha_desde' => $request->fecha_desde,
                    'fecha_hasta' => $request->fecha_hasta,
                    'numero_pedido' => $request->numero_pedido,
                    'cliente' => $request->cliente,
                    'vencidos' => $request->vencidos,
                    'proximos_vencer' => $request->proximos_vencer,
                ],
            ],
        ]);
    }

    /**
     * Mostrar pedido específico
     */
    public function show(Proveedor $proveedor, Pedido $pedido)
    {
        $this->authorize('viewProveedorPedidos', $proveedor);

        if ($pedido->requisicion->proveedor_id !== $proveedor->id) {
            return response()->json(['error' => 'Este pedido no pertenece a su proveedor'], 403);
        }

        $pedido->load([
            'requisicion.usuario',
            'cotizacion.detalles',
            'detalles.cotizacionDetalle.requisicionDetalle.producto',
        ]);

        return new PedidoResource($pedido);
    }

    /**
     * Actualizar estatus del pedido
     */
    public function updateStatus(PedidoUpdateRequest $request, Proveedor $proveedor, Pedido $pedido)
    {
        $this->authorize('updateProveedorPedidos', $proveedor);

        if ($pedido->requisicion->proveedor_id !== $proveedor->id) {
            return response()->json(['error' => 'Este pedido no pertenece a su proveedor'], 403);
        }

        DB::beginTransaction();
        try {
            $resultado = $this->pedidoService->actualizarEstatus($pedido, $request->estatus, $request->validated());

            if (! $resultado) {
                return response()->json(['error' => 'No se puede cambiar al estatus solicitado'], 400);
            }

            // Notificar al cliente
            NotificacionService::enviarCambioEstatusPedido($pedido);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estatus actualizado correctamente',
                'pedido' => new PedidoResource($pedido->fresh()),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['error' => 'Error al actualizar el estatus: '.$e->getMessage()], 500);
        }
    }

    /**
     * Preparar envío del pedido
     */
    public function prepareShipment(Request $request, Proveedor $proveedor, Pedido $pedido)
    {
        $this->authorize('updateProveedorPedidos', $proveedor);

        $request->validate([
            'numero_guia' => 'required|string|max:50',
            'transportista' => 'required|string|max:100',
            'fecha_envio' => 'nullable|date',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        if ($pedido->requisicion->proveedor_id !== $proveedor->id) {
            return response()->json(['error' => 'Este pedido no pertenece a su proveedor'], 403);
        }

        if (! in_array($pedido->estatus, ['listo_para_entrega', 'en_preparacion'])) {
            return response()->json(['error' => 'El pedido debe estar listo para entrega'], 400);
        }

        DB::beginTransaction();
        try {
            $pedido->update([
                'estatus' => 'en_transito',
                'numero_guia' => $request->numero_guia,
                'transportista' => $request->transportista,
                'observaciones' => $request->observaciones,
                'fecha_envio' => $request->fecha_envio ?? now(),
            ]);

            // Notificar al cliente
            NotificacionService::enviarPedidoEnTransito($pedido);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Envío preparado correctamente',
                'pedido' => new PedidoResource($pedido->fresh()),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['error' => 'Error al preparar el envío: '.$e->getMessage()], 500);
        }
    }

    /**
     * Confirmar entrega del pedido
     */
    public function confirmDelivery(Request $request, Proveedor $proveedor, Pedido $pedido)
    {
        $this->authorize('updateProveedorPedidos', $proveedor);

        $request->validate([
            'fecha_entrega' => 'nullable|date',
            'observaciones_entrega' => 'nullable|string|max:1000',
            'evidencia_entrega' => 'nullable|string|max:500',
            'detalles_entrega' => 'nullable|array',
            'detalles_entrega.*.pedido_detalle_id' => 'required|exists:pedido_detalles,id',
            'detalles_entrega.*.cantidad_entregada' => 'required|integer|min:0',
        ]);

        if ($pedido->requisicion->proveedor_id !== $proveedor->id) {
            return response()->json(['error' => 'Este pedido no pertenece a su proveedor'], 403);
        }

        if (! in_array($pedido->estatus, ['en_transito', 'listo_para_entrega'])) {
            return response()->json(['error' => 'El pedido debe estar en tránsito o listo para entrega'], 400);
        }

        DB::beginTransaction();
        try {
            // Actualizar cantidades entregadas si se proporcionan
            if ($request->detalles_entrega) {
                foreach ($request->detalles_entrega as $detalle) {
                    $pedidoDetalle = $pedido->detalles()->findOrFail($detalle['pedido_detalle_id']);
                    $pedidoDetalle->entregarCantidad($detalle['cantidad_entregada']);
                }
            } else {
                // Marcar todos los detalles como entregados
                $pedido->detalles()->each(function ($detalle) {
                    $detalle->marcarComoEntregado();
                });
            }

            // Actualizar el pedido
            $pedido->update([
                'estatus' => 'entregado',
                'fecha_entrega_real' => $request->fecha_entrega ?? now(),
                'observaciones_entrega' => $request->observaciones_entrega,
                'evidencia_entrega' => $request->evidencia_entrega,
            ]);

            // Notificar al cliente
            NotificacionService::enviarPedidoEntregado($pedido);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entrega confirmada correctamente',
                'pedido' => new PedidoResource($pedido->fresh(['detalles'])),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['error' => 'Error al confirmar la entrega: '.$e->getMessage()], 500);
        }
    }

    /**
     * Rechazar pedido
     */
    public function rechazar(Request $request, Proveedor $proveedor, Pedido $pedido)
    {
        $this->authorize('updateProveedorPedidos', $proveedor);

        $request->validate([
            'motivo' => 'required|string|max:1000',
        ]);

        if ($pedido->requisicion->proveedor_id !== $proveedor->id) {
            return response()->json(['error' => 'Este pedido no pertenece a su proveedor'], 403);
        }

        if (! in_array($pedido->estatus, ['confirmado', 'en_preparacion'])) {
            return response()->json(['error' => 'Solo se pueden rechazar pedidos confirmados o en preparación'], 400);
        }

        DB::beginTransaction();
        try {
            $pedido->update([
                'estatus' => 'cancelado',
                'fecha_cancelacion' => now(),
                'motivo_cancelacion' => $request->motivo,
            ]);

            // Notificar al cliente
            NotificacionService::enviarPedidoRechazado($pedido);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido rechazado correctamente',
                'pedido' => new PedidoResource($pedido->fresh()),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['error' => 'Error al rechazar el pedido: '.$e->getMessage()], 500);
        }
    }

    /**
     * Obtener dashboard de pedidos
     */
    public function dashboard(Request $request, Proveedor $proveedor)
    {
        $this->authorize('viewProveedorPedidos', $proveedor);

        $stats = $this->pedidoService->getEstadisticasParaProveedor($proveedor->id);

        // Pedidos recientes
        $pedidosRecientes = $proveedor->pedidos()
            ->with(['requisicion.usuario', 'detalles'])
            ->latest()
            ->limit(10)
            ->get();

        // Pedidos próximos a vencer
        $pedidosProximosVencer = $proveedor->pedidos()
            ->proximosAVencer(7)
            ->with(['requisicion.usuario'])
            ->get();

        // Pedidos vencidos
        $pedidosVencidos = $proveedor->pedidos()
            ->vencidos()
            ->with(['requisicion.usuario'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'estadisticas' => $stats,
                'pedidos_recientes' => PedidoResource::collection($pedidosRecientes),
                'pedidos_proximos_vencer' => PedidoResource::collection($pedidosProximosVencer),
                'pedidos_vencidos' => PedidoResource::collection($pedidosVencidos),
            ],
        ]);
    }

    /**
     * Exportar pedidos del proveedor
     */
    public function exportar(Request $request, Proveedor $proveedor)
    {
        $this->authorize('viewProveedorPedidos', $proveedor);

        $request->validate([
            'formato' => 'required|in:excel,csv,pdf',
            'estatus' => 'nullable|string',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
        ]);

        $pedidos = $proveedor->pedidos()
            ->with(['requisicion.usuario', 'cotizacion', 'detalles.producto'])
            ->when($request->estatus, function ($query, $estatus) {
                $query->where('estatus', $estatus);
            })
            ->when($request->fecha_desde, function ($query, $fecha) {
                $query->whereDate('fecha_confirmacion', '>=', $fecha);
            })
            ->when($request->fecha_hasta, function ($query, $fecha) {
                $query->whereDate('fecha_confirmacion', '<=', $fecha);
            })
            ->get();

        // Aquí iría la lógica de exportación
        $fileName = 'pedidos_proveedor_'.$proveedor->id.'_'.now()->format('Y-m-d_H-i-s').'.'.$request->formato;

        return response()->json([
            'success' => true,
            'message' => 'Exportación iniciada',
            'file_name' => $fileName,
            'download_url' => route('proveedor.pedidos.download', ['proveedor' => $proveedor->id, 'file' => $fileName]),
        ]);
    }
}
