<?php

namespace App\Http\Controllers\Gerente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Presupuesto\StorePresupuestoRequest;
use App\Http\Requests\Presupuesto\UpdatePresupuestoRequest;
use App\Http\Resources\Presupuesto\PresupuestoCollection;
use App\Http\Resources\Presupuesto\PresupuestoResource;
use App\Models\Presupuesto;
use App\Models\PresupuestoConcepto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Controlador de administración para el módulo Presupuesto Básico.
 */
class PresupuestoController extends Controller
{
    private bool $logEnabled = true;

    /**
     * Lista presupuestos con filtros opcionales y paginación.
     * Responsabilidad: consulta y serialización de resultados.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'proveedor_id' => 'nullable|integer|exists:proveedores,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
            'con_iva' => 'nullable|boolean',
            'sort_by' => 'nullable|in:id,numero_presupuesto,fecha_emision,subtotal,total,created_at',
            'order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $sortBy = $request->input('sort_by', 'fecha_emision');
        $order = $request->input('order', 'desc');
        $perPage = (int) $request->input('per_page', 15);

        $query = Presupuesto::query()
            ->with(['proveedor:id,nombre_comercial,razon_social', 'empresaReceptora:id,nombre_comercial,razon_social', 'user:id,name'])
            ->when($request->filled('proveedor_id'), fn ($q) => $q->byProveedor((int) $request->input('proveedor_id')))
            ->when($request->filled('user_id'), fn ($q) => $q->byUser((int) $request->input('user_id')))
            ->byFechaRango($request->input('fecha_desde'), $request->input('fecha_hasta'))
            ->when($request->has('con_iva'), function ($q) use ($request) {
                return filter_var($request->input('con_iva'), FILTER_VALIDATE_BOOLEAN)
                    ? $q->conIva()
                    : $q->sinIva();
            })
            ->orderBy($sortBy, $order);

        $paginator = $query->paginate($perPage);

        return $this->success(new PresupuestoCollection($paginator));
    }

    /**
     * Crea un presupuesto con sus conceptos en una transacción.
     * Responsabilidad: orquestación de persistencia; los cálculos viven en modelos.
     */
    public function store(StorePresupuestoRequest $request): JsonResponse
    {
        try {
            $presupuesto = DB::transaction(function () use ($request) {
                $data = $request->validated();
                $conceptos = $data['conceptos'];
                unset($data['conceptos']);

                $data['user_id'] = $request->user()?->id;
                $data['numero_presupuesto'] = $data['numero_presupuesto']
                    ?? Presupuesto::generarNumeroPresupuesto((int) $data['proveedor_id']);
                $data['con_iva'] = $data['con_iva'] ?? true;
                $data['iva_porcentaje'] = $data['iva_porcentaje'] ?? 16.00;

                $presupuesto = Presupuesto::create($data);

                foreach ($conceptos as $index => $conceptoData) {
                    $concepto = new PresupuestoConcepto([
                        'numero' => $index + 1,
                        'descripcion' => $conceptoData['descripcion'],
                        'cantidad' => $conceptoData['cantidad'],
                        'unidad' => $conceptoData['unidad'],
                        'precio_unitario' => $conceptoData['precio_unitario'],
                    ]);
                    $concepto->calcularImporte();
                    $presupuesto->conceptos()->save($concepto);
                }

                $presupuesto->load('conceptos');
                $presupuesto->calcularTotales();
                $presupuesto->save();

                return $presupuesto;
            });

            $presupuesto->load([
                'proveedor:id,nombre_comercial,razon_social',
                'empresaReceptora:id,nombre_comercial,razon_social',
                'user:id,name',
                'conceptos',
            ]);

            $this->log('Presupuesto creado', [
                'presupuesto_id' => $presupuesto->id,
                'numero_presupuesto' => $presupuesto->numero_presupuesto,
            ]);

            return $this->success(
                new PresupuestoResource($presupuesto),
                'Presupuesto creado correctamente.',
                201
            );
        } catch (Throwable $e) {
            $this->log('Error al crear presupuesto', ['error' => $e->getMessage()]);

            return $this->error('No fue posible crear el presupuesto.', $e->getMessage(), 500);
        }
    }

    /**
     * Muestra un presupuesto con sus relaciones principales.
     * Responsabilidad: lectura detallada del recurso.
     */
    public function show(Presupuesto $presupuesto): JsonResponse
    {
        $presupuesto->load([
            'proveedor:id,nombre_comercial,razon_social',
            'empresaReceptora:id,nombre_comercial,razon_social',
            'user:id,name',
            'conceptos',
        ]);

        return $this->success(new PresupuestoResource($presupuesto));
    }

    /**
     * Actualiza cabecera y conceptos en transacción, recalculando importes desde modelo.
     * Responsabilidad: actualización atómica del agregado Presupuesto.
     */
    public function update(UpdatePresupuestoRequest $request, Presupuesto $presupuesto): JsonResponse
    {
        try {
            $presupuesto = DB::transaction(function () use ($request, $presupuesto) {
                $data = $request->validated();
                $conceptos = $data['conceptos'];
                unset($data['conceptos']);

                if (empty($data['numero_presupuesto'])) {
                    $data['numero_presupuesto'] = $presupuesto->numero_presupuesto
                        ?: Presupuesto::generarNumeroPresupuesto((int) $data['proveedor_id']);
                }

                $presupuesto->fill($data);
                $presupuesto->save();

                $presupuesto->conceptos()->delete();
                foreach ($conceptos as $index => $conceptoData) {
                    $concepto = new PresupuestoConcepto([
                        'numero' => $index + 1,
                        'descripcion' => $conceptoData['descripcion'],
                        'cantidad' => $conceptoData['cantidad'],
                        'unidad' => $conceptoData['unidad'],
                        'precio_unitario' => $conceptoData['precio_unitario'],
                    ]);
                    $concepto->calcularImporte();
                    $presupuesto->conceptos()->save($concepto);
                }

                $presupuesto->load('conceptos');
                $presupuesto->calcularTotales();
                $presupuesto->save();

                return $presupuesto;
            });

            $presupuesto->load([
                'proveedor:id,nombre_comercial,razon_social',
                'empresaReceptora:id,nombre_comercial,razon_social',
                'user:id,name',
                'conceptos',
            ]);

            $this->log('Presupuesto actualizado', [
                'presupuesto_id' => $presupuesto->id,
                'numero_presupuesto' => $presupuesto->numero_presupuesto,
            ]);

            return $this->success(
                new PresupuestoResource($presupuesto),
                'Presupuesto actualizado correctamente.'
            );
        } catch (Throwable $e) {
            $this->log('Error al actualizar presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible actualizar el presupuesto.', $e->getMessage(), 500);
        }
    }

    /**
     * Elimina un presupuesto y sus conceptos por cascada.
     * Responsabilidad: remoción del recurso.
     */
    public function destroy(Presupuesto $presupuesto): JsonResponse
    {
        try {
            $presupuesto->delete();

            $this->log('Presupuesto eliminado', [
                'presupuesto_id' => $presupuesto->id,
                'numero_presupuesto' => $presupuesto->numero_presupuesto,
            ]);

            return $this->success(null, 'Presupuesto eliminado correctamente.');
        } catch (Throwable $e) {
            $this->log('Error al eliminar presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible eliminar el presupuesto.', $e->getMessage(), 500);
        }
    }

    /**
     * Registro de eventos internos del módulo.
     *
     * @param  array<string, mixed>  $data
     */
    private function log($message, $data = []): void
    {
        if (! $this->logEnabled) {
            return;
        }

        Log::info($message, $data);
    }
}

