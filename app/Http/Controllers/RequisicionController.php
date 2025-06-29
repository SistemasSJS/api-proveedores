<?php

namespace App\Http\Controllers;

use App\Http\Requests\Requisicion\RequisicionStoreRequest;
use App\Http\Resources\RequisicionResource;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class RequisicionController extends Controller
{
    public function index(Request $request)
    {
        $requisiciones = Auth::user()->requisiciones()
            ->with(['proveedor', 'detalles.producto'])
            ->when($request->estatus, function ($query, $estatus) {
                $query->where('estatus', $estatus);
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return RequisicionResource::collection($requisiciones);
    }

    public function store(RequisicionStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $requisicion = Auth::user()->requisiciones()->create([
                'proveedor_id' => $request->proveedor_id,
                'fecha_requerida' => $request->fecha_requerida,
                'observaciones' => $request->observaciones,
                'estatus' => 'pendiente',
                'total_estimado' => 0,
            ]);

            $totalEstimado = 0;
            foreach ($request->productos as $productoData) {
                $producto = Producto::find($productoData['producto_id']);
                $subtotal = $producto->precio_base * $productoData['cantidad'];
                $totalEstimado += $subtotal;

                $requisicion->detalles()->create([
                    'producto_id' => $productoData['producto_id'],
                    'cantidad' => $productoData['cantidad'],
                    'precio_unitario_estimado' => $producto->precio_base,
                    'subtotal_estimado' => $subtotal,
                    'observaciones' => $productoData['observaciones'] ?? null,
                ]);
            }

            $requisicion->update(['total_estimado' => $totalEstimado]);

            // Enviar notificación al proveedor
            NotificacionService::enviarNuevaRequisicion($requisicion);

            DB::commit();
            return new RequisicionResource($requisicion->load(['proveedor', 'detalles.producto']));
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al crear la requisición'], 500);
        }
    }

    public function show(Requisicion $requisicion)
    {
        $this->authorize('view', $requisicion);
        return new RequisicionResource($requisicion->load(['proveedor', 'detalles.producto', 'cotizacion']));
    }

    public function cancelar(Request $request, Requisicion $requisicion)
    {
        $this->authorize('update', $requisicion);

        if (!in_array($requisicion->estatus, ['pendiente', 'en_proceso'])) {
            return response()->json(['error' => 'No se puede cancelar esta requisición'], 400);
        }

        $requisicion->update([
            'estatus' => 'cancelada',
            'fecha_cancelacion' => now(),
            'motivo_cancelacion' => $request->motivo,
        ]);

        NotificacionService::enviarRequisicionCancelada($requisicion);
        return response()->json(['message' => 'Requisición cancelada correctamente']);
    }
}
