<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\CotizacionDetalle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Controlador para la gestión de cotizaciones en el módulo de construcción
 * 
 * Maneja todas las operaciones CRUD de cotizaciones con sus detalles asociados.
 * Incluye validaciones complejas y manejo de transacciones para garantizar
 * la integridad de los datos entre cotizaciones y sus detalles.
 */
class ConstruccCotizacionController extends Controller
{
    /**
     * Lista paginada de cotizaciones con filtros opcionales
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
            'estado' => 'nullable|in:vigentes,vencidas,todas',
            'sort_by' => 'nullable|in:fecha_cotizacion,fecha_vencimiento,total,created_at',
            'order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $query = Cotizacion::with(['proveedor:id,nombre_comercial,rfc', 'detalles.producto:id,nombre'])
            ->when($request->proveedor_id, function ($q, $proveedorId) {
                $q->where('proveedor_id', $proveedorId);
            })
            ->when($request->fecha_desde, function ($q, $fechaDesde) {
                $q->where('fecha_cotizacion', '>=', $fechaDesde);
            })
            ->when($request->fecha_hasta, function ($q, $fechaHasta) {
                $q->where('fecha_cotizacion', '<=', $fechaHasta);
            });

        // Aplicar filtro de estado (vigentes/vencidas)
        if ($request->estado === 'vigentes') {
            $query->vigentes();
        } elseif ($request->estado === 'vencidas') {
            $query->vencidas();
        }

        $sortBy = $request->sort_by ?? 'fecha_cotizacion';
        $order = $request->order ?? 'desc';
        $perPage = $request->per_page ?? 20;

        $cotizaciones = $query->orderBy($sortBy, $order)
            ->paginate($perPage);

        return $this->paginated($cotizaciones, 'Cotizaciones obtenidas correctamente.');
    }

    /**
     * Crea una nueva cotización con sus detalles
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha_cotizacion' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_cotizacion',
            'observaciones' => 'nullable|string|max:1000',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad_cotizada' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.tiempo_entrega_dias' => 'nullable|integer|min:0',
            'detalles.*.observaciones' => 'nullable|string|max:500',
        ]);

        try {
            $cotizacion = DB::transaction(function () use ($validatedData) {
                // Calcular el total de la cotización
                $total = collect($validatedData['detalles'])->sum(function ($detalle) {
                    return $detalle['cantidad_cotizada'] * $detalle['precio_unitario'];
                });

                // Crear la cotización
                $cotizacion = Cotizacion::create([
                    'proveedor_id' => $validatedData['proveedor_id'],
                    'fecha_cotizacion' => $validatedData['fecha_cotizacion'],
                    'fecha_vencimiento' => $validatedData['fecha_vencimiento'],
                    'total' => $total,
                    'observaciones' => $validatedData['observaciones'],
                ]);

                // Crear los detalles de la cotización
                foreach ($validatedData['detalles'] as $detalle) {
                    CotizacionDetalle::create([
                        'proveedor_id' => $validatedData['proveedor_id'],
                        'cotizacion_id' => $cotizacion->id,
                        'producto_id' => $detalle['producto_id'],
                        'cantidad_cotizada' => $detalle['cantidad_cotizada'],
                        'precio_unitario' => $detalle['precio_unitario'],
                        'subtotal' => $detalle['cantidad_cotizada'] * $detalle['precio_unitario'],
                        'tiempo_entrega_dias' => $detalle['tiempo_entrega_dias'],
                        'observaciones' => $detalle['observaciones'],
                    ]);
                }

                return $cotizacion;
            });

            // Cargar las relaciones para la respuesta
            $cotizacion->load(['proveedor:id,nombre_comercial,rfc', 'detalles.producto:id,nombre']);

            return $this->success($cotizacion, 'Cotización creada exitosamente.', 201);
        } catch (\Exception $e) {
            return $this->error('Error al crear la cotización: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Muestra una cotización específica con sus detalles
     *
     * @param Cotizacion $cotizacion
     * @return JsonResponse
     */
    public function show(Cotizacion $cotizacion): JsonResponse
    {
        $cotizacion->load(['proveedor:id,nombre_comercial,rfc,telefono,email', 'detalles.producto:id,nombre,descripcion']);

        return $this->success($cotizacion, 'Cotización obtenida correctamente.');
    }

    /**
     * Actualiza una cotización existente
     *
     * @param Request $request
     * @param Cotizacion $cotizacion
     * @return JsonResponse
     */
    public function update(Request $request, Cotizacion $cotizacion): JsonResponse
    {
        $validatedData = $request->validate([
            'proveedor_id' => 'sometimes|exists:proveedores,id',
            'fecha_cotizacion' => 'sometimes|date',
            'fecha_vencimiento' => 'sometimes|date|after_or_equal:fecha_cotizacion',
            'observaciones' => 'nullable|string|max:1000',
            'detalles' => 'sometimes|array|min:1',
            'detalles.*.id' => 'nullable|exists:cotizacion_detalles,id',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad_cotizada' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.tiempo_entrega_dias' => 'nullable|integer|min:0',
            'detalles.*.observaciones' => 'nullable|string|max:500',
            'detalles.*.eliminar' => 'nullable|boolean',
        ]);

        try {
            $cotizacionActualizada = DB::transaction(function () use ($cotizacion, $validatedData) {
                // Actualizar datos básicos de la cotización
                $datosBasicos = collect($validatedData)->except(['detalles'])->toArray();
                if (!empty($datosBasicos)) {
                    $cotizacion->update($datosBasicos);
                }

                // Si se enviaron detalles, procesarlos
                if (isset($validatedData['detalles'])) {
                    $this->procesarDetalles($cotizacion, $validatedData['detalles'], $validatedData['proveedor_id'] ?? $cotizacion->proveedor_id);
                }

                // Recalcular el total de la cotización
                $nuevoTotal = $cotizacion->detalles()->sum('subtotal');
                $cotizacion->update(['total' => $nuevoTotal]);

                return $cotizacion;
            });

            // Cargar las relaciones para la respuesta
            $cotizacionActualizada->load(['proveedor:id,nombre_comercial,rfc', 'detalles.producto:id,nombre']);

            return $this->success($cotizacionActualizada, 'Cotización actualizada exitosamente.');
        } catch (\Exception $e) {
            return $this->error('Error al actualizar la cotización: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Elimina una cotización y todos sus detalles
     *
     * @param Cotizacion $cotizacion
     * @return JsonResponse
     */
    public function destroy(Cotizacion $cotizacion): JsonResponse
    {
        try {
            DB::transaction(function () use ($cotizacion) {
                // Eliminar primero los detalles
                $cotizacion->detalles()->delete();
                
                // Luego eliminar la cotización
                $cotizacion->delete();
            });

            return $this->success(null, 'Cotización eliminada exitosamente.');
        } catch (\Exception $e) {
            return $this->error('Error al eliminar la cotización: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Procesa los detalles de una cotización (crear, actualizar, eliminar)
     *
     * @param Cotizacion $cotizacion
     * @param array $detalles
     * @param int $proveedorId
     * @return void
     */
    private function procesarDetalles(Cotizacion $cotizacion, array $detalles, int $proveedorId): void
    {
        foreach ($detalles as $detalle) {
            $subtotal = $detalle['cantidad_cotizada'] * $detalle['precio_unitario'];

            if (isset($detalle['id'])) {
                // Detalle existente
                $detalleExistente = CotizacionDetalle::find($detalle['id']);
                
                if ($detalleExistente && $detalleExistente->cotizacion_id === $cotizacion->id) {
                    if (isset($detalle['eliminar']) && $detalle['eliminar']) {
                        // Eliminar detalle
                        $detalleExistente->delete();
                    } else {
                        // Actualizar detalle existente
                        $detalleExistente->update([
                            'producto_id' => $detalle['producto_id'],
                            'cantidad_cotizada' => $detalle['cantidad_cotizada'],
                            'precio_unitario' => $detalle['precio_unitario'],
                            'subtotal' => $subtotal,
                            'tiempo_entrega_dias' => $detalle['tiempo_entrega_dias'],
                            'observaciones' => $detalle['observaciones'],
                        ]);
                    }
                }
            } else {
                // Nuevo detalle
                CotizacionDetalle::create([
                    'proveedor_id' => $proveedorId,
                    'cotizacion_id' => $cotizacion->id,
                    'producto_id' => $detalle['producto_id'],
                    'cantidad_cotizada' => $detalle['cantidad_cotizada'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal' => $subtotal,
                    'tiempo_entrega_dias' => $detalle['tiempo_entrega_dias'],
                    'observaciones' => $detalle['observaciones'],
                ]);
            }
        }
    }
}
