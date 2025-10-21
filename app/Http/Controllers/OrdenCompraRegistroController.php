<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrdenCompraRequest;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrdenCompraRegistroController extends Controller
{
    /**
     * Registrar una nueva orden de compra desde el frontend
     */
    public function store(StoreOrdenCompraRequest $request, Proveedor $proveedor): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Crear la orden de compra
            $ordenCompra = OrdenCompra::create([
                'numero_orden' => $request->numero_orden,
                'fecha_orden' => $request->fecha_orden,
                'proveedor_id' => $proveedor->id,
                'empresa_construcc_id' => $request->empresa_construcc_id,
                'importe_total' => $request->importe_total,
                'estado' => $request->estado,
                'fecha_aprobacion' => $request->fecha_aprobacion,
                'observaciones' => $request->observaciones,
                'metadata_json' => $request->metadata_json,
                'monto_sp_asociado' => 0,
                'sp_count' => 0,
            ]);

            // Crear los detalles
            foreach ($request->detalles as $detalleData) {
                OrdenCompraDetalle::create([
                    'orden_compra_id' => $ordenCompra->id,
                    'producto' => $detalleData['producto'],
                    'descripcion' => $detalleData['descripcion'] ?? null,
                    'cantidad' => $detalleData['cantidad'],
                    'unidad_medida' => $detalleData['unidad_medida'] ?? null,
                    'precio_unitario' => $detalleData['precio_unitario'],
                ]);
            }

            DB::commit();

            return $this->success(
                $ordenCompra->load(['detalles', 'proveedor', 'empresaConstrucc']),
                'Orden de compra registrada exitosamente.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error al registrar la orden de compra: '.$e->getMessage(), 500);
        }
    }

    /**
     * Actualizar o crear orden de compra basándose en numero_orden
     */
    public function upsert(Request $request, Proveedor $proveedor): JsonResponse
    {
        // Validación personalizada para upsert
        $validator = Validator::make($request->all(), [
            'numero_orden' => 'required|string|max:255',
            'fecha_orden' => 'required|date',
            'empresa_construcc_id' => 'required|integer|exists:empresa_construcc,id',
            'importe_total' => 'required|numeric|min:0.01',
            'estado' => 'required|string',
            'fecha_aprobacion' => 'nullable|date',
            'observaciones' => 'nullable|string|max:1000',
            'metadata_json' => 'nullable|array',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto' => 'required|string|max:255',
            'detalles.*.descripcion' => 'nullable|string|max:500',
            'detalles.*.cantidad' => 'required|numeric|min:0.001',
            'detalles.*.unidad_medida' => 'nullable|string|max:50',
            'detalles.*.precio_unitario' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return $this->error('Datos de validación incorrectos', 422, $validator->errors());
        }

        try {
            DB::beginTransaction();

            // Buscar orden existente
            $ordenCompra = OrdenCompra::where('numero_orden', $request->numero_orden)
                ->where('proveedor_id', $proveedor->id)
                ->first();

            if ($ordenCompra) {
                // Actualizar orden existente
                $ordenCompra->update([
                    'fecha_orden' => $request->fecha_orden,
                    'empresa_construcc_id' => $request->empresa_construcc_id,
                    'importe_total' => $request->importe_total,
                    'estado' => $request->estado,
                    'fecha_aprobacion' => $request->fecha_aprobacion,
                    'observaciones' => $request->observaciones,
                    'metadata_json' => $request->metadata_json,
                ]);

                // Sincronizar detalles
                $this->syncDetalles($ordenCompra, $request->detalles);
                $mensaje = 'Orden de compra actualizada exitosamente.';
                $codigo = 200;
            } else {
                // Crear nueva orden
                $ordenCompra = OrdenCompra::create([
                    'numero_orden' => $request->numero_orden,
                    'fecha_orden' => $request->fecha_orden,
                    'proveedor_id' => $proveedor->id,
                    'empresa_construcc_id' => $request->empresa_construcc_id,
                    'importe_total' => $request->importe_total,
                    'estado' => $request->estado,
                    'fecha_aprobacion' => $request->fecha_aprobacion,
                    'observaciones' => $request->observaciones,
                    'metadata_json' => $request->metadata_json,
                    'monto_sp_asociado' => 0,
                    'sp_count' => 0,
                ]);

                // Crear detalles
                foreach ($request->detalles as $detalleData) {
                    OrdenCompraDetalle::create([
                        'orden_compra_id' => $ordenCompra->id,
                        'producto' => $detalleData['producto'],
                        'descripcion' => $detalleData['descripcion'] ?? null,
                        'cantidad' => $detalleData['cantidad'],
                        'unidad_medida' => $detalleData['unidad_medida'] ?? null,
                        'precio_unitario' => $detalleData['precio_unitario'],
                    ]);
                }

                $mensaje = 'Orden de compra creada exitosamente.';
                $codigo = 201;
            }

            DB::commit();

            return $this->success(
                $ordenCompra->load(['detalles', 'proveedor', 'empresaConstrucc']),
                $mensaje,
                $codigo
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error al procesar la orden de compra: '.$e->getMessage(), 500);
        }
    }

    /**
     * Registrar múltiples órdenes de compra en una sola petición
     */
    public function storeBatch(Request $request, Proveedor $proveedor): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ordenes' => 'required|array|min:1|max:50',
            'ordenes.*.numero_orden' => 'required|string|max:255',
            'ordenes.*.fecha_orden' => 'required|date',
            'ordenes.*.empresa_construcc_id' => 'required|integer|exists:empresa_construcc,id',
            'ordenes.*.importe_total' => 'required|numeric|min:0.01',
            'ordenes.*.estado' => 'required|string',
            'ordenes.*.detalles' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error('Datos de validación incorrectos', 422, $validator->errors());
        }

        $resultados = ['exitosas' => [], 'fallidas' => []];

        foreach ($request->ordenes as $index => $ordenData) {
            try {
                DB::beginTransaction();

                $ordenCompra = OrdenCompra::create([
                    'numero_orden' => $ordenData['numero_orden'],
                    'fecha_orden' => $ordenData['fecha_orden'],
                    'proveedor_id' => $proveedor->id,
                    'empresa_construcc_id' => $ordenData['empresa_construcc_id'],
                    'importe_total' => $ordenData['importe_total'],
                    'estado' => $ordenData['estado'],
                    'fecha_aprobacion' => $ordenData['fecha_aprobacion'] ?? null,
                    'observaciones' => $ordenData['observaciones'] ?? null,
                    'metadata_json' => $ordenData['metadata_json'] ?? null,
                    'monto_sp_asociado' => 0,
                    'sp_count' => 0,
                ]);

                // Crear detalles
                foreach ($ordenData['detalles'] as $detalleData) {
                    OrdenCompraDetalle::create([
                        'orden_compra_id' => $ordenCompra->id,
                        'producto' => $detalleData['producto'],
                        'descripcion' => $detalleData['descripcion'] ?? null,
                        'cantidad' => $detalleData['cantidad'],
                        'unidad_medida' => $detalleData['unidad_medida'] ?? null,
                        'precio_unitario' => $detalleData['precio_unitario'],
                    ]);
                }

                DB::commit();
                $resultados['exitosas'][] = ['index' => $index, 'numero_orden' => $ordenData['numero_orden']];
            } catch (\Exception $e) {
                DB::rollBack();
                $resultados['fallidas'][] = [
                    'index' => $index,
                    'numero_orden' => $ordenData['numero_orden'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->success($resultados, 'Procesamiento por lotes completado.');
    }

    /**
     * Verificar si una orden de compra ya existe
     */
    public function checkExistence(Request $request, Proveedor $proveedor): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'numero_orden' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Datos de validación incorrectos', 422, $validator->errors());
        }

        $exists = OrdenCompra::where('numero_orden', $request->numero_orden)
            ->where('proveedor_id', $proveedor->id)
            ->exists();

        return $this->success([
            'exists' => $exists,
            'numero_orden' => $request->numero_orden,
        ]);
    }

    /**
     * Sincronizar detalles de una orden de compra
     */
    private function syncDetalles(OrdenCompra $ordenCompra, array $nuevosDetalles): void
    {
        // Eliminar detalles existentes
        $ordenCompra->detalles()->delete();

        // Crear nuevos detalles
        foreach ($nuevosDetalles as $detalleData) {
            OrdenCompraDetalle::create([
                'orden_compra_id' => $ordenCompra->id,
                'producto' => $detalleData['producto'],
                'descripcion' => $detalleData['descripcion'] ?? null,
                'cantidad' => $detalleData['cantidad'],
                'unidad_medida' => $detalleData['unidad_medida'] ?? null,
                'precio_unitario' => $detalleData['precio_unitario'],
            ]);
        }
    }
}
