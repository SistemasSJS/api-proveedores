<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sucursal\AsignarProductosRequest;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\Producto;
use App\Services\SucursalService;
use App\Http\Resources\ProductoResource;
use Illuminate\Http\Request;

class SucursalProductoController extends Controller
{
    protected $sucursalService;

    public function __construct(SucursalService $sucursalService)
    {
        $this->sucursalService = $sucursalService;
    }

    public function index(Request $request, Proveedor $proveedor, Sucursal $sucursal)
    {
        $productos = $sucursal->productos()
            ->with(['marca', 'linea', 'categoria'])
            ->when($request->buscar, function ($query, $buscar) {
                $query->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('sku', 'like', "%{$buscar}%");
            })
            ->when($request->con_stock, function ($query) {
                $query->wherePivot('stock_local', '>', 0);
            })
            ->when($request->sin_stock, function ($query) {
                $query->wherePivot('stock_local', '=', 0);
            })
            ->when($request->stock_bajo, function ($query) {
                $stockMinimo = $request->stock_minimo ?? 10;
                $query->wherePivot('stock_local', '<=', $stockMinimo);
            })
            ->paginate($request->per_page ?? 15);

        return ProductoResource::collection($productos);
    }

    public function asignarProductos(AsignarProductosRequest $request, Proveedor $proveedor, Sucursal $sucursal)
    {
        $resultado = $this->sucursalService->asignarProductos(
            $sucursal->id,
            $request->productos
        );

        return response()->json([
            'success' => $resultado,
            'message' => 'Productos asignados correctamente'
        ]);
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

    public function updateStock(Request $request, Proveedor $proveedor, Sucursal $sucursal)
    {
        $request->validate([
            'actualizaciones' => 'required|array',
            'actualizaciones.*.producto_id' => 'required|exists:productos,id',
            'actualizaciones.*.stock_local' => 'required|integer|min:0',
            'actualizaciones.*.precio_local' => 'nullable|numeric|min:0',
            'actualizaciones.*.activo' => 'boolean',
        ]);

        $resultado = $this->sucursalService->actualizarStockMasivo(
            $sucursal->id,
            $request->actualizaciones
        );

        return response()->json([
            'success' => $resultado,
            'message' => 'Stock actualizado correctamente'
        ]);
    }

    public function transferirStock(Request $request)
    {
        $request->validate([
            'sucursal_origen_id' => 'required|exists:sucursales,id',
            'sucursal_destino_id' => 'required|exists:sucursales,id|different:sucursal_origen_id',
            'transferencias' => 'required|array',
            'transferencias.*.producto_id' => 'required|exists:productos,id',
            'transferencias.*.cantidad' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:500',
        ]);

        try {
            $this->sucursalService->transferirStock(
                $request->sucursal_origen_id,
                $request->sucursal_destino_id,
                $request->transferencias
            );

            return response()->json(['message' => 'Transferencia realizada correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function reporteInventario(Request $request, Proveedor $proveedor, Sucursal $sucursal)
    {
        $productos = $sucursal->productos()
            ->with(['marca', 'linea', 'categoria'])
            ->get();

        $reporte = [
            'sucursal' => [
                'id' => $sucursal->id,
                'nombre' => $sucursal->nombre,
                'direccion' => $sucursal->direccion,
            ],
            'resumen' => [
                'total_productos' => $productos->count(),
                'productos_activos' => $productos->where('pivot.activo', true)->count(),
                'productos_con_stock' => $productos->where('pivot.stock_local', '>', 0)->count(),
                'productos_sin_stock' => $productos->where('pivot.stock_local', 0)->count(),
                'valor_total_inventario' => $productos->sum(function ($producto) {
                    return $producto->pivot->stock_local * ($producto->pivot->precio_local ?? $producto->precio_base);
                }),
            ],
            'productos' => $productos->map(function ($producto) {
                return [
                    'id' => $producto->id,
                    'sku' => $producto->sku,
                    'nombre' => $producto->nombre,
                    'marca' => $producto->marca?->nombre,
                    'categoria' => $producto->categoria?->nombre,
                    'stock_local' => $producto->pivot->stock_local,
                    'precio_local' => $producto->pivot->precio_local,
                    'precio_base' => $producto->precio_base,
                    'activo' => $producto->pivot->activo,
                    'valor_inventario' => $producto->pivot->stock_local * ($producto->pivot->precio_local ?? $producto->precio_base),
                ];
            })
        ];

        return response()->json(['data' => $reporte]);
    }
}
