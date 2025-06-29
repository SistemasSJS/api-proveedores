<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sucursal\SucursalStoreRequest;
use App\Http\Requests\Sucursal\SucursalUpdateRequest;
use App\Http\Resources\SucursalResource;
use App\Models\Proveedor;
use App\Models\Sucursal;
use Illuminate\Support\Facades\Request;

class ProveedorSucursalController extends Controller
{
    public function index(Request $request, Proveedor $proveedor)
    {
        $sucursales = $proveedor->sucursales()
            ->when($request->buscar, function ($query, $buscar) {
                $query->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('direccion', 'like', "%{$buscar}%");
            })
            ->paginate($request->per_page ?? 15);

        return SucursalResource::collection($sucursales);
    }

    public function store(SucursalStoreRequest $request, Proveedor $proveedor)
    {
        $sucursal = $proveedor->sucursales()->create($request->validated());
        return new SucursalResource($sucursal);
    }

    public function show(Proveedor $proveedor, Sucursal $sucursal)
    {
        return new SucursalResource($sucursal->load('productos'));
    }

    public function update(SucursalUpdateRequest $request, Proveedor $proveedor, Sucursal $sucursal)
    {
        $sucursal->update($request->validated());
        return new SucursalResource($sucursal);
    }

    public function destroy(Proveedor $proveedor, Sucursal $sucursal)
    {
        $sucursal->delete();
        return response()->json(['message' => 'Sucursal eliminada correctamente']);
    }
}
