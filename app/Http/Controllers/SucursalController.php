<?php

namespace App\Http\Controllers;

use App\Enums\EstadoGeneral;
use App\Http\Requests\Sucursal\SucursalStoreRequest;
use App\Http\Requests\Sucursal\SucursalUpdateRequest;
use App\Http\Resources\SucursalResource;
use App\Models\Sucursal;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class SucursalController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'nombre' => 'nullable|string|max:255',
            'estatus' => 'nullable|in:' . implode(',', EstadoGeneral::values()),
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Sucursal::with('proveedor')
            ->where('estatus', EstadoGeneral::ACTIVO->value)
            ->when($request->filled('nombre'), fn($q) => $q->where('nombre', 'like', '%' . $request->nombre . '%'))
            ->when($request->filled('estatus'), fn($q) => $q->where('estatus', $request->estatus))
            ->when($request->filled('proveedor_id'), fn($q) => $q->where('proveedor_id', $request->proveedor_id));
        $perPage = $request->input('per_page', 10);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => SucursalResource::collection($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }


    public function indexGroupedByProveedor(Request $request)
    {
        $request->validate([
            'nombre' => 'nullable|string|max:255',
            'estatus' => 'nullable|in:' . implode(',', EstadoGeneral::values()),
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);


        $query = Sucursal::with('proveedor')
            ->where('estatus', EstadoGeneral::ACTIVO->value)
            ->when($request->filled('nombre'), fn($q) => $q->where('nombre', 'like', '%' . $request->nombre . '%'))
            ->when($request->filled('estatus'), fn($q) => $q->where('estatus', $request->estatus))
            ->when($request->filled('proveedor_id'), fn($q) => $q->where('proveedor_id', $request->proveedor_id));
        $perPage = $request->input('per_page', 10);
        $paginator = $query->paginate($perPage);

        $grouped = collect($paginator->items())
            ->groupBy('proveedor_id')
            ->map(function ($sucursales) {
                $proveedor = $sucursales->first()->proveedor;
                return [
                    'proveedor_id' => $proveedor->id,
                    'proveedor_nombre' => $proveedor->nombre_comercial,
                    'sucursales' => SucursalResource::collection($sucursales),
                ];
            })->values();

        return response()->json([
            'data' => $grouped,
            'pagination' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $reuqest, $sucursalId)
    {
        $sucursal = Sucursal::with(Sucursal::eagerLodable())->findOrFail($sucursalId);
        return $this->success(new SucursalResource($sucursal));
    }

    public function store(SucursalStoreRequest $request)
    {
        $proveedor = Proveedor::findOrFail($request->input('proveedor_id'));
        $sucursal = $proveedor->sucursales()->create($request->validated());
        return $this->success(new SucursalResource($sucursal), 'Sucursal creada correctamente');
    }


    public function update(SucursalUpdateRequest $request, $sucursalId)
    {
        $proveedor = Proveedor::findOrFail($request->input('proveedor_id'));
        $sucursal = $proveedor->sucursales()->findOrFail($sucursalId);
        $sucursal->update($request->validated());
        return $this->success(new SucursalResource($sucursal), 'Sucursal actualizada correctamente');
    }


    public function destroy(Request $request, $sucursalId)
    {
        $sucursal = Sucursal::findOrFail($sucursalId);
        // Desactivación lógica
        $sucursal->update([
            'activa' => false,
            'estatus' => EstadoGeneral::ELIMINADO->value,
        ]);
        return $this->success(new SucursalResource($sucursal), 'Sucursal desactivada correctamente');
    }
}
