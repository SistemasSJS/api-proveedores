<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Requests\Sucursal\SucursalStoreRequest;
use App\Http\Requests\Sucursal\SucursalUpdateRequest;
use App\Http\Resources\SucursalResource;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProveedorSucursalController extends Controller
{
    use ApiResponse;

    public function index(Request $request, Proveedor $proveedor)
    {
        /**
         * Filtrado dinámico: ?nombre=matriz&activa=1&estatus=activa
         * Paginación y ordenamiento: ?per_page=10&sort_by=nombre&order=asc
         */
        $filters = $request->only(Sucursal::$filters ?? []);
        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'nombre');
        $order = $request->input('order', 'asc');

        $query = Sucursal::query()
            ->filter($filters ?? [])
            ->delProveedor($proveedor->id)
            ->orderBy($sortBy, $order);

        $paginator = $query->paginate($perPage);

        return $this->paginated($paginator);
    }

    public function show(Request $request, Proveedor $proveedor, $sucursalId)
    {
        $sucursal = Sucursal::with(Sucursal::eagerLodable())->findOrFail($sucursalId);

        if ($sucursal->proveedor_id !== $proveedor->id) {
            throw new ResourceNotFoundException("Sucursal no relacionada al proveedor.");
        }

        return $this->success(new SucursalResource($sucursal));
    }

    public function store(SucursalStoreRequest $request, Proveedor $proveedor)
    {
        $data = $request->validated();
        $data['proveedor_id'] = $proveedor->id;

        $sucursal = Sucursal::create($data);

        return $this->success(new SucursalResource($sucursal));
    }

    public function update(SucursalUpdateRequest $request, Proveedor $proveedor, $sucursalId)
    {
        $sucursal = Sucursal::findOrFail($sucursalId);

        if ($sucursal->proveedor_id !== $proveedor->id) {
            throw new ResourceNotFoundException("Sucursal no relacionada al proveedor.");
        }

        $sucursal->update($request->validated());

        return $this->success(new SucursalResource($sucursal->fresh()));
    }

    public function destroy(Request $request, Proveedor $proveedor, $sucursalId)
    {
        $sucursal = Sucursal::findOrFail($sucursalId);

        if ($sucursal->proveedor_id !== $proveedor->id) {
            throw new ResourceNotFoundException("Sucursal no relacionada al proveedor.");
        }

        $sucursal->delete();

        return $this->success(message: "Sucursal eliminada correctamente.");
    }
}
