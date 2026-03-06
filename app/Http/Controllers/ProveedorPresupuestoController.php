<?php

namespace App\Http\Controllers;

use App\Http\Requests\Presupuesto\StorePresupuestoRequest;
use App\Http\Requests\Presupuesto\UpdatePresupuestoRequest;
use App\Http\Resources\Presupuesto\PresupuestoResource;
use App\Models\CarteraCliente;
use App\Models\Presupuesto;
use App\Models\PresupuestoConcepto;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Controlador de administración para el módulo Presupuesto Básico.
 */
class ProveedorPresupuestoController extends Controller
{
    private bool $logEnabled = true;

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

            if (! empty($validated['empresa_receptora_id']) && ! CarteraCliente::query()
                ->where('proveedor_id', $proveedor->id)
                ->whereKey((int) $validated['empresa_receptora_id'])
                ->exists()) {
                return $this->error('El cliente de cartera no pertenece al proveedor indicado.', null, 422);
            }

            $presupuesto = DB::transaction(function () use ($request, $validated) {
                $payload = collect($validated)->except(['conceptos'])->toArray();
                $payload['user_id'] = $request->user()->id;
                $payload['proveedor_id'] = (int) $validated['proveedor_id'];
                $payload['numero_presupuesto'] = $payload['numero_presupuesto']
                    ?? Presupuesto::generarNumeroPresupuesto((int) $payload['proveedor_id']);
                $payload['con_iva'] = $payload['con_iva'] ?? true;
                $payload['iva_porcentaje'] = $payload['iva_porcentaje'] ?? 16.00;
                $payload = $this->normalizarEmpresaReceptora($payload, (int) $payload['proveedor_id']);

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

    public function show(Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        if ($presupuesto->proveedor_id !== $proveedor->id) {
            return $this->error('Presupuesto no pertenece a este proveedor.', null, 403);
        }

        $presupuesto->load(Presupuesto::eagerLodable());

        return $this->success(new PresupuestoResource($presupuesto));
    }

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

            if (! empty($validated['empresa_receptora_id']) && ! CarteraCliente::query()
                ->where('proveedor_id', $proveedor->id)
                ->whereKey((int) $validated['empresa_receptora_id'])
                ->exists()) {
                return $this->error('El cliente de cartera no pertenece al proveedor indicado.', null, 422);
            }

            $presupuesto = DB::transaction(function () use ($validated, $presupuesto) {
                $payload = collect($validated)->except(['conceptos'])->toArray();
                $payload['proveedor_id'] = (int) $validated['proveedor_id'];
                $payload['numero_presupuesto'] = $payload['numero_presupuesto'] ?? $presupuesto->numero_presupuesto;
                $payload = $this->normalizarEmpresaReceptora($payload, (int) $payload['proveedor_id']);

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
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizarEmpresaReceptora(array $payload, int $proveedorId): array
    {
        if (empty($payload['empresa_receptora_id'])) {
            return $payload;
        }

        $cliente = CarteraCliente::query()
            ->where('proveedor_id', $proveedorId)
            ->findOrFail((int) $payload['empresa_receptora_id']);

        $payload['empresa_receptora_nombre'] = $cliente->nombre;
        $payload['empresa_receptora_puesto'] = $cliente->puesto;
        $payload['empresa_receptora_empresa'] = $cliente->empresa;
        $payload['empresa_receptora_telefono'] = $cliente->telefono;
        $payload['empresa_receptora_correo'] = $cliente->correo;

        return $payload;
    }

    /**
     * @param array<int, array<string, mixed>> $conceptos
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

    private function log($message, $data = []): void
    {
        if (! $this->logEnabled) {
            return;
        }

        Log::info($message, $data);
    }
}
