<?php

namespace App\Http\Controllers;

use App\Http\Requests\Requisicion\CotizacionStoreRequest;
use App\Http\Resources\CotizacionResource;
use App\Http\Resources\RequisicionResource;
use App\Models\Proveedor;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class ProveedorRequisicionController extends Controller
{
    public function index(Request $request, Proveedor $proveedor)
    {
        $requisiciones = $proveedor->requisiciones()
            ->with(['usuario', 'detalles.producto'])
            ->when($request->estatus, function ($query, $estatus) {
                $query->where('estatus', $estatus);
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return RequisicionResource::collection($requisiciones);
    }

    public function show(Proveedor $proveedor, Requisicion $requisicion)
    {
        return new RequisicionResource($requisicion->load(['usuario', 'detalles.producto', 'cotizacion']));
    }

    public function cambiarEstatus(Request $request, Proveedor $proveedor, Requisicion $requisicion)
    {
        $request->validate([
            'estatus' => 'required|in:en_proceso,cotizada,rechazada,entregada',
            'observaciones' => 'nullable|string',
        ]);

        $requisicion->update([
            'estatus' => $request->estatus,
            'observaciones_proveedor' => $request->observaciones,
        ]);

        NotificacionService::enviarCambioEstatusRequisicion($requisicion);
        return response()->json(['message' => 'Estatus actualizado correctamente']);
    }

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
            $requisicion->update(['estatus' => 'cotizada']);

            NotificacionService::enviarCotizacionGenerada($requisicion);

            DB::commit();
            return new CotizacionResource($cotizacion->load('detalles'));
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al generar la cotización'], 500);
        }
    }
}
