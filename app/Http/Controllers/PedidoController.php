<?php

namespace App\Http\Controllers;


use App\Http\Requests\Pedido\PedidoStoreRequest;
use App\Http\Requests\Pedido\PedidoUpdateRequest;
use App\Http\Resources\PedidoResource;
use App\Models\Pedido;
use App\Models\Cotizacion;
use App\Services\PedidoService;
use App\Services\NotificacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    protected $pedidoService;

    public function __construct(PedidoService $pedidoService)
    {
        $this->pedidoService = $pedidoService;
    }

    /**
     * Listar pedidos del usuario autenticado
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Pedido::class);

        $query = Auth::user()->pedidos()
            ->with(['requisicion', 'cotizacion', 'detalles.producto', 'proveedor']);

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
            $query->where('numero_pedido', 'like', '%' . $request->numero_pedido . '%');
        }

        if ($request->proveedor_id) {
            $query->whereHas('requisicion', function ($q) use ($request) {
                $q->where('proveedor_id', $request->proveedor_id);
            });
        }

        // Ordenamiento
        $orderBy = $request->get('order_by', 'fecha_confirmacion');
        $orderDirection = $request->get('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        $pedidos = $query->paginate($request->get('per_page', 15));

        return PedidoResource::collection($pedidos);
    }

    /**
     * Crear pedido desde cotización
     */
    public function store(PedidoStoreRequest $request)
    {
        $this->authorize('create', Pedido::class);

        $cotizacion = Cotizacion::with(['requisicion', 'detalles'])->findOrFail($request->cotizacion_id);

        // Verificar que la cotización pertenece al usuario autenticado
        if ($cotizacion->requisicion->usuario_id !== Auth::id()) {
            return response()->json(['error' => 'No tienes permisos para crear un pedido de esta cotización'], 403);
        }

        DB::beginTransaction();
        try {
            $pedido = $this->pedidoService->crearDesdeCotizacion($cotizacion, $request->validated());

            // Enviar notificaciones
            NotificacionService::enviarPedidoCreado($pedido);

            DB::commit();

            return new PedidoResource($pedido->load([
                'requisicion',
                'cotizacion',
                'detalles.producto',
                'proveedor'
            ]));
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al crear el pedido: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar pedido específico
     */
    public function show(Pedido $pedido)
    {
        $this->authorize('view', $pedido);

        $pedido->load([
            'requisicion.usuario',
            'cotizacion.detalles',
            'detalles.cotizacionDetalle.requisicionDetalle.producto',
            'proveedor'
        ]);

        return new PedidoResource($pedido);
    }

    /**
     * Actualizar estatus del pedido
     */
    public function updateStatus(PedidoUpdateRequest $request, Pedido $pedido)
    {
        $this->authorize('update', $pedido);

        // Solo el cliente puede cancelar pedidos
        if ($request->estatus === 'cancelado') {
            $resultado = $this->pedidoService->cancelar($pedido, $request->motivo_cancelacion);

            if (!$resultado) {
                return response()->json(['error' => 'No se puede cancelar el pedido en su estado actual'], 400);
            }

            // Notificar al proveedor
            NotificacionService::enviarPedidoCancelado($pedido);

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado correctamente',
                'pedido' => new PedidoResource($pedido->fresh())
            ]);
        }

        // Otras actualizaciones de estatus no permitidas para el cliente
        return response()->json(['error' => 'No tienes permisos para actualizar este estatus'], 403);
    }

    /**
     * Cancelar pedido
     */
    public function cancel(Request $request, Pedido $pedido)
    {
        $this->authorize('update', $pedido);

        $request->validate([
            'motivo' => 'required|string|max:1000'
        ]);

        $resultado = $this->pedidoService->cancelar($pedido, $request->motivo);

        if (!$resultado) {
            return response()->json(['error' => 'No se puede cancelar el pedido en su estado actual'], 400);
        }

        // Notificar al proveedor
        NotificacionService::enviarPedidoCancelado($pedido);

        return response()->json([
            'success' => true,
            'message' => 'Pedido cancelado correctamente',
            'pedido' => new PedidoResource($pedido->fresh())
        ]);
    }

    /**
     * Obtener estadísticas de pedidos del usuario
     */
    public function estadisticas(Request $request)
    {
        $this->authorize('viewAny', Pedido::class);

        $stats = $this->pedidoService->getEstadisticasParaUsuario(Auth::id());

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Duplicar pedido
     */
    public function duplicar(Pedido $pedido)
    {
        $this->authorize('view', $pedido);

        if ($pedido->estatus !== 'entregado') {
            return response()->json(['error' => 'Solo se pueden duplicar pedidos entregados'], 400);
        }

        DB::beginTransaction();
        try {
            $nuevoPedido = $this->pedidoService->duplicar($pedido);

            DB::commit();

            return new PedidoResource($nuevoPedido->load([
                'requisicion',
                'cotizacion',
                'detalles.producto',
                'proveedor'
            ]));
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al duplicar el pedido: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Confirmar recepción de pedido
     */
    public function confirmarRecepcion(Request $request, Pedido $pedido)
    {
        $this->authorize('update', $pedido);

        $request->validate([
            'observaciones' => 'nullable|string|max:1000',
            'calificacion' => 'nullable|integer|min:1|max:5'
        ]);

        if ($pedido->estatus !== 'entregado') {
            return response()->json(['error' => 'Solo se pueden confirmar pedidos entregados'], 400);
        }

        $pedido->update([
            'estatus' => 'facturado',
            'observaciones_entrega' => $request->observaciones,
            'calificacion_cliente' => $request->calificacion
        ]);

        // Notificar al proveedor
        NotificacionService::enviarRecepcionConfirmada($pedido);

        return response()->json([
            'success' => true,
            'message' => 'Recepción confirmada correctamente',
            'pedido' => new PedidoResource($pedido->fresh())
        ]);
    }

    /**
     * Exportar pedidos
     */
    public function exportar(Request $request)
    {
        $this->authorize('viewAny', Pedido::class);

        $request->validate([
            'formato' => 'required|in:excel,csv,pdf',
            'estatus' => 'nullable|string',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
        ]);

        $pedidos = Auth::user()->pedidos()
            ->with(['requisicion', 'cotizacion', 'detalles.producto', 'proveedor'])
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
        $fileName = 'pedidos_' . Auth::id() . '_' . now()->format('Y-m-d_H-i-s') . '.' . $request->formato;

        return response()->json([
            'success' => true,
            'message' => 'Exportación iniciada',
            'file_name' => $fileName,
            'download_url' => route('pedidos.download', ['file' => $fileName])
        ]);
    }
}
