<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductoResource;
use App\Models\Proveedor;
use App\Models\Sucursal;
use Illuminate\Support\Facades\Request;

class SucursalProductoController extends Controller
{
    public function index(Request $request, Proveedor $proveedor, Sucursal $sucursal)
    {
        $productos = $sucursal->productos()
            ->with(['marca', 'linea', 'categoria'])
            ->when($request->buscar, function ($query, $buscar) {
                $query->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('sku', 'like', "%{$buscar}%");
            })
            ->paginate($request->per_page ?? 15);

        return ProductoResource::collection($productos);
    }

    public function asignarProductos(Request $request, Proveedor $proveedor, Sucursal $sucursal)
    {
        $request->validate([
            'productos' => 'required|array',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.stock_local' => 'required|integer|min:0',
            'productos.*.precio_local' => 'nullable|numeric|min:0',
        ]);

        foreach ($request->productos as $producto) {
            $sucursal->productos()->syncWithoutDetaching([
                $producto['id'] => [
                    'stock_local' => $producto['stock_local'],
                    'precio_local' => $producto['precio_local'] ?? null,
                    'activo' => true,
                ]
            ]);
        }

        return response()->json(['message' => 'Productos asignados correctamente']);
    }

    public function desasignarProductos(Request $request, Proveedor $proveedor, Sucursal $sucursal)
    {
        $request->validate([
            'productos' => 'required|array',
            'productos.*' => 'required|exists:productos,id',
        ]);

        $sucursal->productos()->detach($request->productos);
        return response()->json(['message' => 'Productos desasignados correctamente']);
    }

    public function updateStock(Request $request, Proveedor $proveedor, Sucursal $sucursal, Producto $producto)
    {
        $request->validate([
            'stock_local' => 'required|integer|min:0',
            'precio_local' => 'nullable|numeric|min:0',
            'activo' => 'boolean',
        ]);

        $sucursal->productos()->updateExistingPivot($producto->id, [
            'stock_local' => $request->stock_local,
            'precio_local' => $request->precio_local,
            'activo' => $request->activo ?? true,
        ]);

        return response()->json(['message' => 'Stock actualizado correctamente']);
    }
}
