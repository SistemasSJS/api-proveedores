<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Resources\ProductoCatalogoResource;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\ProductoSearchService;
use App\Http\Resources\ProductoResource;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class ProductoBusquedaController extends Controller
{
    protected $searchService;

    public function __construct(ProductoSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function buscar(Request $request)
    {
        $request->validate([
            'buscar' => 'nullable|string|min:2',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'marca_id' => 'nullable|exists:marcas,id',
            'linea_id' => 'nullable|exists:lineas,id',
            'precio_min' => 'nullable|numeric|min:0',
            'precio_max' => 'nullable|numeric|min:0',
            // 'con_stock' => 'nullable|boolean',
            'orden_por' => 'nullable|in:nombre,precio_base,stock,created_at',
            'direccion' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);
        // $con_stock = filter_var($request->input('con_stock'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $filtros = [
            'buscar' => $request->buscar,
            'proveedor_id' => $request->proveedor_id,
            'categoria_id' => $request->categoria_id,
            'marca_id' => $request->marca_id,
            'linea_id' => $request->linea_id,
            'precio_min' => $request->precio_min,
            'precio_max' => $request->precio_max,
            // 'con_stock' => $con_stock,
            'orden_por' => $request->orden_por ?? 'nombre',
            'direccion' => $request->direccion ?? 'asc',
            'per_page' => $request->per_page ?? 20,
        ];

        $productos = $this->searchService->buscar($filtros);

        $data = ProductoCatalogoResource::collection($productos)->resolve();
        return $this->paginated($productos->setCollection(collect($data)));
    }

    public function buscarParaRequisicion(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'termino' => 'required|string|min:2',
            'limite' => 'nullable|integer|min:5|max:50',
        ]);

        $productos = $this->searchService->buscarParaRequisicion(
            $proveedor->id,
            $request->termino
        );

        // Limitar resultados si se especifica
        if ($request->limite) {
            $productos = array_slice($productos, 0, $request->limite);
        }

        return response()->json(['productos' => $productos]);
    }

    public function verificarDisponibilidad(Producto $producto, Request $request)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $disponibilidad = [
            'producto_id' => $producto->id,
            'cantidad_solicitada' => $request->cantidad,
            'stock_general' => $producto->stock,
            'disponible_general' => $producto->stock >= $request->cantidad,
        ];

        if ($request->sucursal_id) {
            $sucursal = Sucursal::find($request->sucursal_id);
            $stockLocal = $sucursal->productos()
                ->where('producto_id', $producto->id)
                ->first()?->pivot?->stock_local ?? 0;

            $disponibilidad['stock_sucursal'] = $stockLocal;
            $disponibilidad['disponible_sucursal'] = $stockLocal >= $request->cantidad;
            $disponibilidad['sucursal'] = [
                'id' => $sucursal->id,
                'nombre' => $sucursal->nombre,
            ];
        }

        return response()->json(['data' => $disponibilidad]);
    }

    public function sugerencias(Request $request)
    {
        $request->validate([
            'termino' => 'required|string|min:1',
            'proveedor_id' => 'nullable|exists:proveedores,id',
        ]);

        $query = Producto::where('activo', true)
            ->where(function ($q) use ($request) {
                $q->where('nombre', 'like', $request->termino . '%')
                    ->orWhere('sku', 'like', $request->termino . '%');
            });

        if ($request->proveedor_id) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        $sugerencias = $query->limit(10)
            ->get(['id', 'nombre', 'sku', 'precio_base'])
            ->map(function ($producto) {
                return [
                    'id' => $producto->id,
                    'texto' => $producto->nombre . ' (' . $producto->sku . ')',
                    'precio' => $producto->precio_base,
                ];
            });

        return response()->json(['sugerencias' => $sugerencias]);
    }

    public function filtrosDisponibles(Request $request)
    {
        $filtros = [
            'proveedores' => Proveedor::where('estatus', 'activo')
                ->has('productos')
                ->get(['id', 'nombre_comercial']),
            'categorias' => \App\Models\Categoria::whereHas('productos')
                ->get(['id', 'nombre']),
            'marcas' => \App\Models\Marca::whereHas('productos')
                ->get(['id', 'nombre']),
            'rango_precios' => [
                'min' => Producto::where('activo', true)->min('precio_base'),
                'max' => Producto::where('activo', true)->max('precio_base'),
            ],
        ];

        return response()->json(['filtros' => $filtros]);
    }
}
