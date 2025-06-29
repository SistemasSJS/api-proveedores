<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Services\RequisicionService;
use App\Services\NotificacionService;
use App\Http\Resources\RequisicionResource;
use App\Http\Requests\Requisicion\CotizacionStoreRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProveedorRequisicionController extends Controller
{
    protected $requisicionService;

    public function __construct(RequisicionService $requisicionService)
    {
        $this->requisicionService = $requisicionService;
    }

    /**
     * Listar requisiciones del proveedor con estadísticas
     */
    public function index(Request $request, Proveedor $proveedor)
    {
        // AHORA SÍ EXISTE este método
        $stats = $this->requisicionService->getEstadisticasParaProveedor($proveedor->id);

        $requisiciones = $proveedor->requisiciones()
            ->with(['usuario', 'detalles.producto'])
            ->when($request->estatus, function ($query, $estatus) {
                $query->where('estatus', $estatus);
            })
            ->when($request->fecha_desde, function ($query, $fecha) {
                $query->whereDate('created_at', '>=', $fecha);
            })
            ->when($request->fecha_hasta, function ($query, $fecha) {
                $query->whereDate('created_at', '<=', $fecha);
            })
            ->when($request->buscar, function ($query, $buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('numero_requisicion', 'like', "%{$buscar}%")
                        ->orWhere('observaciones', 'like', "%{$buscar}%")
                        ->orWhereHas('usuario', function ($userQuery) use ($buscar) {
                            $userQuery->where('name', 'like', "%{$buscar}%");
                        });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => [
                'requisiciones' => RequisicionResource::collection($requisiciones),
                'estadisticas' => $stats,
                'filtros_aplicados' => [
                    'estatus' => $request->estatus,
                    'fecha_desde' => $request->fecha_desde,
                    'fecha_hasta' => $request->fecha_hasta,
                    'buscar' => $request->buscar,
                ]
            ]
        ]);
    }

    /**
     * Mostrar requisición específica
     */
    public function show(Proveedor $proveedor, Requisicion $requisicion)
    {
        $requisicion->load(['usuario', 'detalles.producto', 'cotizacion.detalles']);
        return new RequisicionResource($requisicion);
    }

    /**
     * Cambiar estatus de requisición
     */
    public function cambiarEstatus(Request $request, Proveedor $proveedor, Requisicion $requisicion)
    {
        $request->validate([
            'estatus' => 'required|in:en_proceso,cotizada,rechazada,entregada',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $resultado = $this->requisicionService->cambiarEstatus(
            $requisicion,
            $request->estatus,
            $request->observaciones
        );

        if (!$resultado) {
            return response()->json(['error' => 'Cambio de estatus no permitido'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estatus actualizado correctamente',
            'requisicion' => new RequisicionResource($requisicion->fresh())
        ]);
    }

    /**
     * Generar cotización para requisición
     */
    public function generarCotizacion(CotizacionStoreRequest $request, Proveedor $proveedor, Requisicion $requisicion)
    {
        if ($requisicion->cotizacion) {
            return response()->json(['error' => 'Esta requisición ya tiene una cotización'], 400);
        }

        DB::beginTransaction();
        try {
            $cotizacion = $requisicion->cotizacion()->create([
                'fecha_cotizacion' => now(),
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'total' => 0,
                'observaciones' => $request->observaciones,
            ]);

            $total = 0;
            foreach ($request->detalles as $detalle) {
                $subtotal = $detalle['precio_unitario'] * $detalle['cantidad_cotizada'];
                $total += $subtotal;

                $cotizacion->detalles()->create([
                    'requisicion_detalle_id' => $detalle['requisicion_detalle_id'],
                    'cantidad_cotizada' => $detalle['cantidad_cotizada'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal' => $subtotal,
                    'tiempo_entrega_dias' => $detalle['tiempo_entrega_dias'],
                    'observaciones' => $detalle['observaciones'] ?? null,
                ]);
            }

            $cotizacion->update(['total' => $total]);

            // Usar RequisicionService para cambiar estatus
            $this->requisicionService->cambiarEstatus($requisicion, 'cotizada');

            // Usar NotificacionService para notificar
            NotificacionService::enviarCotizacionGenerada($requisicion);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotización generada correctamente',
                'cotizacion' => new \App\Http\Resources\CotizacionResource($cotizacion->load('detalles'))
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al generar la cotización: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Rechazar requisición
     */
    public function rechazar(Request $request, Proveedor $proveedor, Requisicion $requisicion)
    {
        $request->validate([
            'motivo' => 'required|string|max:500'
        ]);

        $resultado = $this->requisicionService->cambiarEstatus(
            $requisicion,
            'rechazada',
            $request->motivo
        );

        if (!$resultado) {
            return response()->json(['error' => 'No se puede rechazar esta requisición'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Requisición rechazada correctamente'
        ]);
    }

    /**
     * Marcar requisición como entregada
     */
    public function marcarEntregada(Request $request, Proveedor $proveedor, Requisicion $requisicion)
    {
        $request->validate([
            'fecha_entrega' => 'nullable|date',
            'observaciones_entrega' => 'nullable|string|max:500',
        ]);

        if ($requisicion->estatus !== 'cotizada') {
            return response()->json(['error' => 'Solo se pueden entregar requisiciones cotizadas'], 400);
        }

        $resultado = $this->requisicionService->cambiarEstatus(
            $requisicion,
            'entregada',
            $request->observaciones_entrega
        );

        if ($resultado) {
            // Actualizar fecha de entrega si se proporciona
            if ($request->fecha_entrega) {
                $requisicion->update(['fecha_entrega' => $request->fecha_entrega]);
            }
        }

        return response()->json([
            'success' => $resultado,
            'message' => $resultado ? 'Requisición marcada como entregada' : 'Error al marcar como entregada'
        ]);
    }

    /**
     * Obtener resumen de requisiciones del proveedor
     */
    public function resumen(Request $request, Proveedor $proveedor)
    {
        $periodo = $request->input('periodo', 30); // días

        $fechaInicio = now()->subDays($periodo);

        $requisiciones = $proveedor->requisiciones()
            ->where('created_at', '>=', $fechaInicio)
            ->get();

        $resumen = [
            'periodo_dias' => $periodo,
            'total_requisiciones' => $requisiciones->count(),
            'monto_total' => $requisiciones->whereIn('estatus', ['cotizada', 'entregada'])->sum('total_estimado'),
            'por_estatus' => $requisiciones->groupBy('estatus')->map->count(),
            'clientes_unicos' => $requisiciones->pluck('usuario_id')->unique()->count(),
            'productos_mas_solicitados' => $this->getProductosMasSolicitados($proveedor->id, $periodo),
            'dias_con_actividad' => $requisiciones->pluck('created_at')->map->toDateString()->unique()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }

    /**
     * Obtener productos más solicitados del proveedor
     */
    private function getProductosMasSolicitados(int $proveedorId, int $dias): array
    {
        return DB::table('requisicion_detalles')
            ->join('requisiciones', 'requisicion_detalles.requisicion_id', '=', 'requisiciones.id')
            ->join('productos', 'requisicion_detalles.producto_id', '=', 'productos.id')
            ->where('requisiciones.proveedor_id', $proveedorId)
            ->where('requisiciones.created_at', '>=', now()->subDays($dias))
            ->select(
                'productos.id',
                'productos.nombre',
                'productos.sku',
                DB::raw('SUM(requisicion_detalles.cantidad) as total_solicitado'),
                DB::raw('COUNT(DISTINCT requisicion_detalles.requisicion_id) as veces_solicitado')
            )
            ->groupBy('productos.id', 'productos.nombre', 'productos.sku')
            ->orderBy('total_solicitado', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
