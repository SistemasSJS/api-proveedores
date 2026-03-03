<?php

namespace App\Http\Controllers\Gerente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Presupuesto\StorePresupuestoRequest;
use App\Http\Requests\Presupuesto\UpdatePresupuestoRequest;
use App\Http\Resources\Presupuesto\PresupuestoResource;
use App\Models\Presupuesto;
use App\Models\PresupuestoConcepto;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Collection;
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
     * Listado paginado de presupuestos.
     */
    public function index(Request $request, Proveedor $proveedor): JsonResponse
    {
        $filters = $request->only(Presupuesto::getFilters());
        $filters['proveedor_id'] = $proveedor->id;
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 10);

        $originalPaginator = Presupuesto::query()
            ->with(Presupuesto::eagerLodable())
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);

        $data = PresupuestoResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    /**
     * Crear presupuesto con conceptos.
     */
    public function store(StorePresupuestoRequest $request, Proveedor $proveedor): JsonResponse
    {
        try {
            $validated = $request->validated();
            $user = $request->user();

            if (! $user || ! method_exists($user, 'tieneAccesoAProveedor') || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
                return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
            }

            if ((int) $validated['proveedor_id'] !== (int) $proveedor->id) {
                return $this->error('El proveedor del payload no coincide con el proveedor de la ruta.', null, 422);
            }

            $presupuesto = DB::transaction(function () use ($request, $validated) {
                $payload = collect($validated)->except(['conceptos'])->toArray();
                $payload['user_id'] = $request->user()->id;
                $payload['proveedor_id'] = (int) $validated['proveedor_id'];
                $payload['numero_presupuesto'] = $payload['numero_presupuesto']
                    ?? Presupuesto::generarNumeroPresupuesto((int) $payload['proveedor_id']);
                $payload['con_iva'] = $payload['con_iva'] ?? true;
                $payload['iva_porcentaje'] = $payload['iva_porcentaje'] ?? 16.00;
                $payload = $this->normalizarEmpresaReceptora($payload);

                $presupuesto = Presupuesto::create($payload);

                $this->sincronizarConceptos($presupuesto, $validated['conceptos']);
                $presupuesto->recalcularDesdeConceptos();
                $presupuesto->save();

                return $presupuesto->fresh(Presupuesto::eagerLodable());
            });

            $this->log('Presupuesto creado', ['presupuesto_id' => $presupuesto->id]);

            return $this->success(
                new PresupuestoResource($presupuesto),
                'Presupuesto creado correctamente.',
                201
            );
        } catch (Throwable $e) {
            $this->log('Error al crear presupuesto', ['error' => $e->getMessage()]);

            return $this->error('No fue posible crear el presupuesto.', [$e->getMessage()], 500);
        }
    }

    /**
     * Mostrar detalle de presupuesto.
     */
    public function show(Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        if ($presupuesto->proveedor_id !== $proveedor->id) {
            return $this->error('Presupuesto no pertenece a este proveedor.', null, 403);
        }

        $presupuesto->load(Presupuesto::eagerLodable());

        return $this->success(new PresupuestoResource($presupuesto));
    }

    /**
     * Actualizar presupuesto y conceptos.
     */
    public function update(UpdatePresupuestoRequest $request, Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        try {
            if ($presupuesto->proveedor_id !== $proveedor->id) {
                return $this->error('Presupuesto no pertenece a este proveedor.', null, 403);
            }

            $validated = $request->validated();
            if ((int) $validated['proveedor_id'] !== (int) $proveedor->id) {
                return $this->error('El proveedor del payload no coincide con el proveedor de la ruta.', null, 422);
            }

            $presupuesto = DB::transaction(function () use ($validated, $presupuesto) {
                $payload = collect($validated)->except(['conceptos'])->toArray();
                $payload['proveedor_id'] = (int) $validated['proveedor_id'];
                $payload['numero_presupuesto'] = $payload['numero_presupuesto'] ?? $presupuesto->numero_presupuesto;
                $payload = $this->normalizarEmpresaReceptora($payload);

                $presupuesto->update($payload);
                $this->sincronizarConceptos($presupuesto, $validated['conceptos']);
                $presupuesto->recalcularDesdeConceptos();
                $presupuesto->save();

                return $presupuesto->fresh(Presupuesto::eagerLodable());
            });

            $this->log('Presupuesto actualizado', ['presupuesto_id' => $presupuesto->id]);

            return $this->success(
                new PresupuestoResource($presupuesto),
                'Presupuesto actualizado correctamente.'
            );
        } catch (Throwable $e) {
            $this->log('Error al actualizar presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible actualizar el presupuesto.', [$e->getMessage()], 500);
        }
    }

    /**
     * Eliminar presupuesto.
     */
    public function destroy(Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        try {
            if ($presupuesto->proveedor_id !== $proveedor->id) {
                return $this->error('Presupuesto no pertenece a este proveedor.', null, 403);
            }

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

            return $this->error('No fue posible eliminar el presupuesto.', [$e->getMessage()], 500);
        }
    }

    /**
     * Normaliza los campos de receptora externa cuando receptora es del sistema.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizarEmpresaReceptora(array $payload): array
    {
        if (! empty($payload['empresa_receptora_id'])) {
            $payload['empresa_receptora_nombre'] = null;
            $payload['empresa_receptora_rfc'] = null;
            $payload['empresa_receptora_direccion'] = null;
            $payload['empresa_receptora_telefono'] = null;
            $payload['empresa_receptora_correo'] = null;
        }

        return $payload;
    }

    /**
     * Reemplaza conceptos actuales por la lista enviada.
     *
     * @param Collection<int, PresupuestoConcepto>|array<int, array<string, mixed>> $conceptos
     * @return void
     */
    private function sincronizarConceptos(Presupuesto $presupuesto, array $conceptos): void
    {
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
    }

    /**
     * Registro de eventos internos.
     */
    private function log($message, $data = []): void
    {
        if (! $this->logEnabled) {
            return;
        }

        Log::info($message, $data);
    }
}
