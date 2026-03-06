<?php

namespace App\Http\Controllers;

use App\Http\Requests\Presupuesto\ProveedorStorePresupuestoCarteraClienteRequest;
use App\Http\Requests\Presupuesto\ProveedorUpdatePresupuestoCarteraClienteRequest;
use App\Http\Resources\Presupuesto\ProveedorPresupuestoCarteraClienteResource;
use App\Models\CarteraCliente;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProveedorPresupuestoCarteraClientesController extends Controller
{
    private bool $logEnabled = true;

    /**
     * Listado de cartera de clientes del proveedor.
     */
    public function index(Request $request, Proveedor $proveedor): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        $filters = $request->only(CarteraCliente::getFilters());
        $filters['proveedor_id'] = $proveedor->id;

        $sortBy = $request->input('sort_by', 'empresa');
        $order = $request->input('order', 'asc');
        $perPage = $request->input('per_page', 15);

        $originalPaginator = CarteraCliente::query()
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);

        $data = ProveedorPresupuestoCarteraClienteResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    /**
     * Crear cliente en cartera.
     */
    public function store(
        ProveedorStorePresupuestoCarteraClienteRequest $request,
        Proveedor $proveedor
    ): JsonResponse {
        try {

            $user = $request->user();

            if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
                return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
            }

            $validated = $request->validated();

            $cliente = CarteraCliente::create([
                'proveedor_id' => $proveedor->id,
                'nombre' => $validated['nombre'],
                'puesto' => $validated['puesto'] ?? null,
                'empresa' => $validated['empresa'],
                'telefono' => $validated['telefono'] ?? null,
                'correo' => $validated['correo'] ?? null,
            ]);

            $this->log('Cliente agregado a cartera', [
                'cliente_id' => $cliente->id,
                'proveedor_id' => $proveedor->id,
            ]);

            return $this->success(
                new ProveedorPresupuestoCarteraClienteResource($cliente),
                'Cliente agregado a cartera.',
                201
            );
        } catch (Throwable $e) {

            $this->log('Error al crear cliente de cartera', [
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible crear el cliente.', [$e->getMessage()], 500);
        }
    }

    /**
     * Mostrar cliente de cartera.
     */
    public function show(
        Request $request,
        Proveedor $proveedor,
        CarteraCliente $carteraCliente
    ): JsonResponse {

        $user = $request->user();

        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
        }

        if ((int) $carteraCliente->proveedor_id !== (int) $proveedor->id) {
            return $this->error('El cliente no pertenece a este proveedor.', null, 403);
        }

        return $this->success(
            new ProveedorPresupuestoCarteraClienteResource($carteraCliente)
        );
    }

    /**
     * Actualizar cliente de cartera.
     */
    public function update(
        ProveedorUpdatePresupuestoCarteraClienteRequest $request,
        Proveedor $proveedor,
        CarteraCliente $carteraCliente
    ): JsonResponse {

        try {

            $user = $request->user();

            if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
                return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
            }

            if ((int) $carteraCliente->proveedor_id !== (int) $proveedor->id) {
                return $this->error('El cliente no pertenece a este proveedor.', null, 403);
            }

            $validated = $request->validated();

            $carteraCliente->update($validated);

            $this->log('Cliente de cartera actualizado', [
                'cliente_id' => $carteraCliente->id,
            ]);

            return $this->success(
                new ProveedorPresupuestoCarteraClienteResource($carteraCliente),
                'Cliente actualizado correctamente.'
            );
        } catch (Throwable $e) {

            $this->log('Error al actualizar cliente de cartera', [
                'cliente_id' => $carteraCliente->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible actualizar el cliente.', [$e->getMessage()], 500);
        }
    }

    /**
     * Eliminar cliente de cartera.
     */
    public function destroy(
        Request $request,
        Proveedor $proveedor,
        CarteraCliente $carteraCliente
    ): JsonResponse {

        try {

            $user = $request->user();

            if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
                return $this->error('El usuario autenticado no tiene acceso al proveedor indicado.', null, 403);
            }

            if ((int) $carteraCliente->proveedor_id !== (int) $proveedor->id) {
                return $this->error('El cliente no pertenece a este proveedor.', null, 403);
            }

            $carteraCliente->delete();

            $this->log('Cliente eliminado de cartera', [
                'cliente_id' => $carteraCliente->id,
            ]);

            return $this->success(null, 'Cliente eliminado de cartera.');
        } catch (Throwable $e) {

            $this->log('Error al eliminar cliente de cartera', [
                'cliente_id' => $carteraCliente->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible eliminar el cliente.', [$e->getMessage()], 500);
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
