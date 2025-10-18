<?php

namespace App\Http\Controllers\Construcc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Construcc\OrdenCompraRequest;
use App\Http\Resources\Construcc\ConstruccOrdenCompraResource;
use App\Http\Resources\Construcc\OrdenCompraConSolicitudesResource;
use App\Models\OrdenCompra;
use App\Models\DetalleOrdenCompra;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrdenCompraController extends Controller
{
    /**
     * Display a listing of the purchase orders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = OrdenCompra::with([
                'proveedor:id,nombre_empresa,rut',
                'empresa:id,nombre',
                'detalles',
                'solicitudesPago' => function ($query) {
                    $query->select('id', 'orden_compra_id', 'numero_solicitud', 'estado', 'monto_total', 'fecha_solicitud');
                }
            ]);

            // Filtros
            if ($request->has('proveedor_id')) {
                $query->where('proveedor_id', $request->proveedor_id);
            }

            if ($request->has('empresa_id')) {
                $query->where('empresa_id', $request->empresa_id);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('fecha_desde')) {
                $query->whereDate('fecha_oc', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->whereDate('fecha_oc', '<=', $request->fecha_hasta);
            }

            if ($request->has('numero_oc')) {
                $query->where('numero_oc', 'like', '%' . $request->numero_oc . '%');
            }

            if ($request->has('departamento')) {
                $query->where('departamento', 'like', '%' . $request->departamento . '%');
            }

            if ($request->has('moneda')) {
                $query->where('moneda', $request->moneda);
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'fecha_oc');
            $sortDirection = $request->get('sort_direction', 'desc');

            $allowedSorts = ['fecha_oc', 'numero_oc', 'valor_total', 'estado', 'fecha_entrega_solicitada'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Paginación
            $perPage = min($request->get('per_page', 15), 100);
            $ordenes = $query->paginate($perPage);

            return response()->json([
                'data' => ConstruccOrdenCompraResource::collection($ordenes->items()),
                'pagination' => [
                    'current_page' => $ordenes->currentPage(),
                    'last_page' => $ordenes->lastPage(),
                    'per_page' => $ordenes->perPage(),
                    'total' => $ordenes->total(),
                    'from' => $ordenes->firstItem(),
                    'to' => $ordenes->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener órdenes de compra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => 'No se pudieron obtener las órdenes de compra'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created purchase order in storage.
     *
     * @param OrdenCompraRequest $request
     * @return JsonResponse
     */
    public function store(OrdenCompraRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();
            $detalles = $validated['detalles'];
            unset($validated['detalles']);

            // Crear la orden de compra
            $orden = OrdenCompra::create($validated);

            // Crear los detalles
            foreach ($detalles as $detalle) {
                $orden->detalles()->create($detalle);
            }

            // Cargar relaciones para la respuesta
            $orden->load([
                'proveedor:id,nombre_empresa,rut',
                'empresa:id,nombre',
                'detalles'
            ]);

            Log::info('Orden de compra creada exitosamente', [
                'orden_id' => $orden->id,
                'numero_oc' => $orden->numero_oc,
                'user_id' => $request->user()->id ?? 'N/A'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Orden de compra creada exitosamente',
                'data' => new ConstruccOrdenCompraResource($orden)
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear orden de compra', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => 'No se pudo crear la orden de compra'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified purchase order.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $incluirSolicitudes = $request->boolean('incluir_solicitudes', false);

            $query = OrdenCompra::with([
                'proveedor:id,nombre_empresa,rut,email,telefono',
                'empresa:id,nombre',
                'detalles'
            ]);

            if ($incluirSolicitudes) {
                $query->with(['solicitudesPago' => function ($query) {
                    $query->select('id', 'orden_compra_id', 'numero_solicitud', 'estado', 'monto_total', 'fecha_solicitud', 'fecha_vencimiento');
                }]);
            }

            $orden = $query->findOrFail($id);

            // $resourceClass = $incluirSolicitudes ? OrdenCompraConSolicitudesResource::class : ConstruccOrdenCompraResource::class;
            $resourceClass =  ConstruccOrdenCompraResource::class;

            return response()->json([
                'data' => new $resourceClass($orden)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'No encontrado',
                'message' => 'La orden de compra solicitada no existe'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Error al obtener orden de compra', [
                'orden_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => 'No se pudo obtener la orden de compra'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified purchase order in storage.
     *
     * @param OrdenCompraRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(OrdenCompraRequest $request, int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $orden = OrdenCompra::findOrFail($id);

            // Verificar si la orden puede ser modificada
            if (in_array($orden->estado, ['confirmada', 'entregada', 'cancelada'])) {
                return response()->json([
                    'error' => 'Acción no permitida',
                    'message' => 'No se puede modificar una orden en estado: ' . $orden->estado
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validated();
            $nuevosDetalles = $validated['detalles'];
            unset($validated['detalles']);

            // Actualizar la orden de compra
            $orden->update($validated);

            // Eliminar detalles existentes y crear los nuevos
            $orden->detalles()->delete();
            foreach ($nuevosDetalles as $detalle) {
                $orden->detalles()->create($detalle);
            }

            // Cargar relaciones para la respuesta
            $orden->load([
                'proveedor:id,nombre_empresa,rut',
                'empresa:id,nombre',
                'detalles'
            ]);

            Log::info('Orden de compra actualizada exitosamente', [
                'orden_id' => $orden->id,
                'numero_oc' => $orden->numero_oc,
                'user_id' => $request->user()->id ?? 'N/A'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Orden de compra actualizada exitosamente',
                'data' => new ConstruccOrdenCompraResource($orden)
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'No encontrado',
                'message' => 'La orden de compra solicitada no existe'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al actualizar orden de compra', [
                'orden_id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => 'No se pudo actualizar la orden de compra'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified purchase order from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $orden = OrdenCompra::findOrFail($id);

            // Verificar si la orden puede ser eliminada
            if (in_array($orden->estado, ['confirmada', 'entregada'])) {
                return response()->json([
                    'error' => 'Acción no permitida',
                    'message' => 'No se puede eliminar una orden en estado: ' . $orden->estado
                ], Response::HTTP_FORBIDDEN);
            }

            // Verificar si tiene solicitudes de pago asociadas
            if ($orden->solicitudesPago()->count() > 0) {
                return response()->json([
                    'error' => 'Acción no permitida',
                    'message' => 'No se puede eliminar una orden que tiene solicitudes de pago asociadas'
                ], Response::HTTP_FORBIDDEN);
            }

            $numeroOc = $orden->numero_oc;

            // Eliminar detalles primero (por la restricción de clave foránea)
            $orden->detalles()->delete();

            // Eliminar la orden
            $orden->delete();

            Log::info('Orden de compra eliminada exitosamente', [
                'orden_id' => $id,
                'numero_oc' => $numeroOc
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Orden de compra eliminada exitosamente'
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'No encontrado',
                'message' => 'La orden de compra solicitada no existe'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al eliminar orden de compra', [
                'orden_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => 'No se pudo eliminar la orden de compra'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the status of the specified purchase order.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'estado' => 'required|string|in:borrador,enviada,confirmada,entregada,cancelada'
            ]);

            $orden = OrdenCompra::findOrFail($id);
            $nuevoEstado = $request->estado;

            // Validar transiciones de estado válidas
            $transicionesValidas = $this->getTransicionesEstadoValidas($orden->estado);

            if (!in_array($nuevoEstado, $transicionesValidas)) {
                return response()->json([
                    'error' => 'Transición no válida',
                    'message' => "No se puede cambiar de '{$orden->estado}' a '{$nuevoEstado}'"
                ], Response::HTTP_BAD_REQUEST);
            }

            $estadoAnterior = $orden->estado;
            $orden->update(['estado' => $nuevoEstado]);

            // Cargar relaciones para la respuesta
            $orden->load([
                'proveedor:id,nombre_empresa,rut',
                'empresa:id,nombre',
                'detalles'
            ]);

            Log::info('Estado de orden de compra actualizado', [
                'orden_id' => $orden->id,
                'numero_oc' => $orden->numero_oc,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado
            ]);

            return response()->json([
                'message' => 'Estado actualizado exitosamente',
                'data' => new ConstruccOrdenCompraResource($orden)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'No encontrado',
                'message' => 'La orden de compra solicitada no existe'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Error al actualizar estado de orden de compra', [
                'orden_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => 'No se pudo actualizar el estado'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get valid state transitions for purchase orders.
     *
     * @param string $estadoActual
     * @return array
     */
    private function getTransicionesEstadoValidas(string $estadoActual): array
    {
        $transiciones = [
            'borrador' => ['enviada', 'cancelada'],
            'enviada' => ['confirmada', 'cancelada'],
            'confirmada' => ['entregada'],
            'entregada' => [], // Estado final
            'cancelada' => [] // Estado final
        ];

        return $transiciones[$estadoActual] ?? [];
    }

    /**
     * Get purchase order statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = OrdenCompra::query();

            // Aplicar filtros de fecha si se proporcionan
            if ($request->has('fecha_desde')) {
                $query->whereDate('fecha_oc', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->whereDate('fecha_oc', '<=', $request->fecha_hasta);
            }

            if ($request->has('empresa_id')) {
                $query->where('empresa_id', $request->empresa_id);
            }

            if ($request->has('proveedor_id')) {
                $query->where('proveedor_id', $request->proveedor_id);
            }

            $estadisticas = [
                'total_ordenes' => $query->count(),
                'por_estado' => $query->groupBy('estado')
                    ->selectRaw('estado, count(*) as cantidad')
                    ->pluck('cantidad', 'estado'),
                'totales_por_moneda' => $query->groupBy('moneda')
                    ->selectRaw('moneda, sum(valor_total) as total, count(*) as cantidad')
                    ->get()
                    ->mapWithKeys(fn($item) => [$item->moneda => [
                        'total' => $item->total,
                        'cantidad' => $item->cantidad
                    ]]),
                'promedio_valor_total' => $query->avg('valor_total'),
                'orden_mas_alta' => $query->max('valor_total'),
                'ordenes_vencidas' => OrdenCompra::where('fecha_entrega_solicitada', '<', now())
                    ->whereIn('estado', ['enviada', 'confirmada'])
                    ->count()
            ];

            return response()->json([
                'data' => $estadisticas
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de órdenes de compra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => 'No se pudieron obtener las estadísticas'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
