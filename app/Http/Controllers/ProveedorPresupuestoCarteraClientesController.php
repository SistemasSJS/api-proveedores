<?php

namespace App\Http\Controllers;

use App\Http\Requests\Presupuesto\ProveedorStorePresupuestoCarteraClienteRequest;
use App\Http\Requests\Presupuesto\ProveedorUpdatePresupuestoCarteraClienteRequest;
use App\Http\Resources\Presupuesto\ProveedorPresupuestoCarteraClienteResource;
use App\Models\CarteraCliente;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProveedorPresupuestoCarteraClientesController extends Controller
{
    /**
     * Listado de cartera de clientes del proveedor.
     */
    public function index(Request $request, Proveedor $proveedor): JsonResponse
    {
        $query = CarteraCliente::query()
            ->byProveedor($proveedor->id)
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = (string) $request->input('search');
                $q->where(function ($sub) use ($term) {
                    $sub->where('nombre', 'like', "%{$term}%")
                        ->orWhere('empresa', 'like', "%{$term}%")
                        ->orWhere('puesto', 'like', "%{$term}%");
                });
            })
            ->orderBy('empresa')
            ->orderBy('nombre');

        if ($request->boolean('all', false)) {
            return $this->success(ProveedorPresupuestoCarteraClienteResource::collection($query->get()));
        }

        $perPage = (int) $request->input('per_page', 15);
        $paginator = $query->paginate($perPage);
        $data = ProveedorPresupuestoCarteraClienteResource::collection($paginator)->resolve();

        return $this->paginated($paginator->setCollection(collect($data)));
    }

    /**
     * Crear cliente en cartera.
     */
    public function store(ProveedorStorePresupuestoCarteraClienteRequest $request, Proveedor $proveedor): JsonResponse
    {
        $validated = $request->validated();

        $cliente = CarteraCliente::create([
            'proveedor_id' => $proveedor->id,
            'nombre' => $validated['nombre'],
            'puesto' => $validated['puesto'] ?? null,
            'empresa' => $validated['empresa'],
            'telefono' => $validated['telefono'] ?? null,
            'correo' => $validated['correo'] ?? null,
        ]);

        return $this->success(
            new ProveedorPresupuestoCarteraClienteResource($cliente),
            'Cliente agregado a cartera.',
            201
        );
    }

    /**
     * Mostrar cliente de cartera.
     */
    public function show(Proveedor $proveedor, CarteraCliente $carteraCliente): JsonResponse
    {
        if ((int) $carteraCliente->proveedor_id !== (int) $proveedor->id) {
            return $this->error('El cliente no pertenece a este proveedor.', null, 403);
        }

        return $this->success(new ProveedorPresupuestoCarteraClienteResource($carteraCliente));
    }

    /**
     * Actualizar cliente de cartera.
     */
    public function update(
        ProveedorUpdatePresupuestoCarteraClienteRequest $request,
        Proveedor $proveedor,
        CarteraCliente $carteraCliente
    ): JsonResponse {
        if ((int) $carteraCliente->proveedor_id !== (int) $proveedor->id) {
            return $this->error('El cliente no pertenece a este proveedor.', null, 403);
        }

        $validated = $request->validated();
        $carteraCliente->update($validated);

        return $this->success(
            new ProveedorPresupuestoCarteraClienteResource($carteraCliente),
            'Cliente actualizado correctamente.'
        );
    }

    /**
     * Eliminar cliente de cartera.
     */
    public function destroy(Proveedor $proveedor, CarteraCliente $carteraCliente): JsonResponse
    {
        if ((int) $carteraCliente->proveedor_id !== (int) $proveedor->id) {
            return $this->error('El cliente no pertenece a este proveedor.', null, 403);
        }

        $carteraCliente->delete();

        return $this->success(null, 'Cliente eliminado de cartera.');
    }
}
