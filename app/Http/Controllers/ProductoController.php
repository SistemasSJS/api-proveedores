<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Exceptions\Api\Crud\DeleteRestrictedException;
use App\Http\Resources\ProductoResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $fields = Producto::getFilters();
        $filters = $request->only($fields);

        $sortBy = $request->input('sort_by', 'nombre'); // Default sort by 'nombre_comercial'
        $order =  $request->input('order', 'asc');
        $perPage = $request->input('per_page', 10);

        $originalPaginator = Producto::query()
            ->with(Producto::eagerLodable())
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);

        $data = ProductoResource::collection($originalPaginator)->resolve();
        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    public function store(Request $request)
    {
        $request->validate([
            'catalogo_id' => 'required|exists:catalogos,id',
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('productos')->where(function ($query) use ($request) {
                    return $query->where('proveedor_id', $request->proveedor_id);
                }),
            ],
            'descripcion' => 'nullable|string',
            'codigo_interno' => [
                'required',
                'string',
                Rule::unique('productos')->where(function ($query) use ($request) {
                    return $query->where('proveedor_id', $request->proveedor_id);
                }),
            ],
            'precio_unitario' => 'required|numeric|min:0',
            'disponible' => 'required|boolean',
            'unidad_medida_id' => 'required|exists:unidad_medidas,id',
        ]);

        $producto = Producto::create($request->all());

        return $this->success($producto->load(["unidad_medida", "imagenes", "catalogo"]), 201);
    }

    public function show($id)
    {
        // Intentar encontrar el producto, si no se encuentra lanzar ResourceNotFoundException
        $producto = Producto::with(Producto::eagerLodable())->find($id);
        if (!$producto) {
            throw new ResourceNotFoundException("Producto no encontrado.");
        }
        return $this->success(new ProductoResource($producto));
    }

    public function update(Request $request, $id)
    {
        $producto = Producto::find($id);
        if (!$producto) {
            throw new ResourceNotFoundException("Producto no encontrado.");
        }
        $producto->update($request->all());
        $producto->load(Producto::eagerLodable());
        return $this->success($producto, 200);
    }

    public function destroy($id)
    {
        // Verificar que el producto exista
        $producto = Producto::find($id);
        if (!$producto) {
            throw new ResourceNotFoundException("Producto no encontrado.");
        }

        // Verificar restricciones de eliminación
        if ($producto->isRestricted()) { // Este es un ejemplo de una posible restricción
            throw new DeleteRestrictedException("Este recurso no puede eliminarse por restricciones.");
        }

        // Eliminar el producto
        $producto->delete();

        return $this->success(null, 204);
    }
}
