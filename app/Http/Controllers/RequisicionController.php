<?php

namespace App\Http\Controllers;

use App\Http\Requests\Requisicion\RequisicionStoreRequest;
use App\Http\Requests\Requisicion\RequisicionUpdateRequest;
use App\Http\Resources\RequisicionResource;
use App\Models\Requisicion;
use App\Services\RequisicionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequisicionController extends Controller
{
    protected $requisicionService;

    public function __construct(RequisicionService $requisicionService)
    {
        $this->requisicionService = $requisicionService;
    }

    /**
     * Listar requisiciones del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $requisiciones = $user->requisiciones()
            ->with(['proveedor', 'detalles.producto'])
            ->when($request->estatus, function ($query, $estatus) {
                $query->where('estatus', $estatus);
            })
            ->when($request->proveedor_id, function ($query, $proveedorId) {
                $query->where('proveedor_id', $proveedorId);
            })
            ->when($request->fecha_desde, function ($query, $fecha) {
                $query->whereDate('created_at', '>=', $fecha);
            })
            ->when($request->fecha_hasta, function ($query, $fecha) {
                $query->whereDate('created_at', '<=', $fecha);
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        $data = RequisicionResource::collection($requisiciones)->resolve();
        return $this->paginated($requisiciones->setCollection(collect($data)));
    }

    /**
     * Mostrar una requisición específica
     */
    public function show(Requisicion $requisicion)
    {
        $this->authorize('view', $requisicion);

        $requisicion->load(['proveedor', 'detalles.producto', 'cotizacion.detalles']);

        return $this->success(new RequisicionResource($requisicion));
    }

    /**
     * Crear nueva requisición
     */
    public function store(RequisicionStoreRequest $request)
    {
        $requisicion = $this->requisicionService->crear(
            $request->validated(),
            Auth::id()
        );

        return $this->success(new RequisicionResource($requisicion));
    }

    /**
     * Actualizar requisición (solo si está pendiente)
     */
    public function update(RequisicionUpdateRequest $request, Requisicion $requisicion)
    {
        $this->authorize('update', $requisicion);

        if (!in_array($requisicion->estatus, ['pendiente'])) {
            return response()->json([
                'error' => 'Solo se pueden modificar requisiciones pendientes'
            ], 400);
        }

        // Solo permitir actualizar observaciones y fecha requerida
        $requisicion->update($request->only(['observaciones', 'fecha_requerida']));

        return $this->success(new RequisicionResource($requisicion->fresh()));
    }

    /**
     * Cancelar requisición
     */
    public function cancelar(Request $request, Requisicion $requisicion)
    {
        $this->authorize('update', $requisicion);

        $request->validate([
            'motivo' => 'required|string|max:500'
        ]);

        $resultado = $this->requisicionService->cancelar(
            $requisicion,
            $request->motivo
        );

        if (!$resultado) {
            return response()->json(['error' => 'No se puede cancelar esta requisición'], 400);
        }

        return $this->success(['message' => 'Requisición cancelada correctamente']);
    }

    /**
     * Eliminar requisición (solo administradores)
     */
    public function destroy(Requisicion $requisicion)
    {
        $this->authorize('delete', $requisicion);

        if (!in_array($requisicion->estatus, ['cancelada', 'rechazada'])) {
            return response()->json([
                'error' => 'Solo se pueden eliminar requisiciones canceladas o rechazadas'
            ], 400);
        }

        $requisicion->delete();

        return $this->success(['message' => 'Requisición eliminada correctamente']);
    }

    /**
     * Obtener estadísticas del usuario
     */
    public function getEstadisticas()
    {
        $stats = $this->requisicionService->getEstadisticasParaUsuario(Auth::id());
        return $this->success(['data' => $stats]);
    }

    /**
     * Obtener requisiciones recientes
     */
    public function recientes(Request $request)
    {
        $limite = $request->input('limite', 5);

        $requisiciones = Auth::user()->requisiciones()
            ->with(['proveedor', 'detalles.producto'])
            ->latest()
            ->limit($limite)
            ->get();

        return $this->success(RequisicionResource::collection($requisiciones));
    }

    /**
     * Buscar requisiciones
     */
    public function buscar(Request $request)
    {
        $request->validate([
            'termino' => 'required|string|min:2',
        ]);

        $termino = $request->termino;

        $requisiciones = Auth::user()->requisiciones()
            ->with(['proveedor', 'detalles.producto'])
            ->where(function ($query) use ($termino) {
                $query->where('numero_requisicion', 'like', "%{$termino}%")
                    ->orWhere('observaciones', 'like', "%{$termino}%")
                    ->orWhereHas('proveedor', function ($q) use ($termino) {
                        $q->where('nombre_comercial', 'like', "%{$termino}%");
                    })
                    ->orWhereHas('detalles.producto', function ($q) use ($termino) {
                        $q->where('nombre', 'like', "%{$termino}%")
                            ->orWhere('sku', 'like', "%{$termino}%");
                    });
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        $data = RequisicionResource::collection($requisiciones)->resolve();
        return $this->paginated($requisiciones->setCollection(collect($data)));
    }

    /**
     * Duplicar requisición
     */
    public function duplicar(Requisicion $requisicion)
    {
        $this->authorize('view', $requisicion);

        $nuevaRequisicion = $this->requisicionService->crear([
            'proveedor_id' => $requisicion->proveedor_id,
            'fecha_requerida' => now()->addDays(3)->toDateString(),
            'observaciones' => 'Duplicada de requisición #' . $requisicion->numero_requisicion,
            'productos' => $requisicion->detalles->map(function ($detalle) {
                return [
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                    'observaciones' => $detalle->observaciones,
                ];
            })->toArray()
        ], Auth::id());

        return $this->success(new RequisicionResource($nuevaRequisicion));
    }

    /**
     * Obtener resumen mensual
     */
    public function resumenMensual(Request $request)
    {
        $año = $request->input('año', date('Y'));
        $mes = $request->input('mes', date('m'));

        $requisiciones = Auth::user()->requisiciones()
            ->whereYear('created_at', $año)
            ->whereMonth('created_at', $mes)
            ->get();

        $resumen = [
            'total' => $requisiciones->count(),
            'por_estatus' => $requisiciones->groupBy('estatus')->map->count(),
            'total_estimado' => $requisiciones->sum('total_estimado'),
            'promedio_por_requisicion' => $requisiciones->count() > 0
                ? $requisiciones->sum('total_estimado') / $requisiciones->count()
                : 0,
            'proveedores_utilizados' => $requisiciones->pluck('proveedor_id')->unique()->count(),
        ];

        return $this->success(['data' => $resumen]);
    }

    /**
     * Exportar requisiciones
     */
    public function exportar(Request $request)
    {
        $request->validate([
            'formato' => 'required|in:excel,csv,pdf',
            'estatus' => 'nullable|string',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
        ]);

        $requisiciones = Auth::user()->requisiciones()
            ->with(['proveedor', 'detalles.producto'])
            ->when($request->estatus, function ($query, $estatus) {
                $query->where('estatus', $estatus);
            })
            ->when($request->fecha_desde, function ($query, $fecha) {
                $query->whereDate('created_at', '>=', $fecha);
            })
            ->when($request->fecha_hasta, function ($query, $fecha) {
                $query->whereDate('created_at', '<=', $fecha);
            })
            ->get();

        // Aquí iría la lógica de exportación
        $fileName = 'requisiciones_' . Auth::id() . '_' . now()->format('Y-m-d');

        return $this->success([
            'message' => 'Exportación en proceso',
            'archivo' => $fileName . '.' . $request->formato
        ]);
    }
}
