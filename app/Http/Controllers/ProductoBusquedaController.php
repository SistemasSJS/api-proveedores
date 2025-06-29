<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Support\Facades\Request;

class ProductoBusquedaController extends Controller
{
    public function buscar(Request $request)
    {
        $request->validate([
            'buscar' => 'required|string|min:2',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'marca_id' => 'nullable|exists:marcas,id',
        ]);

        $productos = Producto::with(['proveedor', 'marca', 'linea', 'categoria'])
            ->where('activo', true)
            ->where(function ($query) use ($request) {
                $query->where('nombre', 'like', "%{$request->buscar}%")
                    ->orWhere('descripcion', 'like', "%{$request->buscar}%")
                    ->orWhere('sku', 'like', "%{$request->buscar}%");
            })
            ->when($request->proveedor_id, function ($query, $proveedorId) {
                $query->where('proveedor_id', $proveedorId);
            })
            ->when($request->categoria_id, function ($query, $categoriaId) {
                $query->where('categoria_id', $categoriaId);
            })
            ->when($request->marca_id, function ($query, $marcaId) {
                $query->where('marca_id', $marcaId);
            })
            ->paginate($request->per_page ?? 20);

        return ProductoResource::collection($productos);
    }

    public function verificarDisponibilidad(Producto $producto, Request $request)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $disponibilidad = [
            'producto_id' => $producto->id,
            'stock_general' => $producto->stock,
            'disponible' => $producto->stock >= $request->cantidad,
        ];

        if ($request->sucursal_id) {
            $sucursal = Sucursal::find($request->sucursal_id);
            $stockLocal = $sucursal->productos()
                ->where('producto_id', $producto->id)
                ->first()?->pivot?->stock_local ?? 0;

            $disponibilidad['stock_sucursal'] = $stockLocal;
            $disponibilidad['disponible_sucursal'] = $stockLocal >= $request->cantidad;
        }

        return response()->json($disponibilidad);
    }
}
