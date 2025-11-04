<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Crud\DeleteRestrictedException;
use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/productos",
     *     summary="Listar productos",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="nombre", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="proveedor_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="categoria_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="sort_by", in="query", required=false, @OA\Schema(type="string", default="nombre")),
     *     @OA\Parameter(name="order", in="query", required=false, @OA\Schema(type="string", enum={"asc", "desc"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=10)),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de productos",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $fields = Producto::getFilters();
        $filters = $request->only($fields);

        $sortBy = $request->input('sort_by', 'nombre'); // Default sort by 'nombre_comercial'
        $order = $request->input('order', 'asc');
        $perPage = $request->input('per_page', 10);

        $originalPaginator = Producto::query()
            ->with(Producto::eagerLodable())
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);

        $data = ProductoResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    /**
     * @OA\Post(
     *     path="/api/productos",
     *     summary="Crear nuevo producto",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"catalogo_id", "nombre", "codigo_interno", "precio_unitario", "disponible", "unidad_medida_id"},
     *             @OA\Property(property="catalogo_id", type="integer", example=1),
     *             @OA\Property(property="nombre", type="string", example="Cemento Portland"),
     *             @OA\Property(property="descripcion", type="string", nullable=true),
     *             @OA\Property(property="codigo_interno", type="string", example="CEM-001"),
     *             @OA\Property(property="precio_unitario", type="number", example=250.50),
     *             @OA\Property(property="disponible", type="boolean", example=true),
     *             @OA\Property(property="unidad_medida_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Producto creado exitosamente"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
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

        return $this->success($producto->load(['unidad_medida', 'imagenes', 'catalogo']), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/productos/{id}",
     *     summary="Obtener producto por ID",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Datos del producto"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function show($id)
    {
        // Intentar encontrar el producto, si no se encuentra lanzar ResourceNotFoundException
        $producto = Producto::with(Producto::eagerLodable())->find($id);
        if (! $producto) {
            throw new ResourceNotFoundException('Producto no encontrado.');
        }

        return $this->success(new ProductoResource($producto));
    }

    /**
     * @OA\Put(
     *     path="/api/productos/{id}",
     *     summary="Actualizar producto",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string"),
     *             @OA\Property(property="precio_unitario", type="number"),
     *             @OA\Property(property="disponible", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Producto actualizado"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function update(Request $request, $id)
    {
        $producto = Producto::find($id);
        if (! $producto) {
            throw new ResourceNotFoundException('Producto no encontrado.');
        }
        $producto->update($request->all());
        $producto->load(Producto::eagerLodable());

        return $this->success($producto, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/productos/{id}",
     *     summary="Eliminar producto",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Producto eliminado"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function destroy($id)
    {
        // Verificar que el producto exista
        $producto = Producto::find($id);
        if (! $producto) {
            throw new ResourceNotFoundException('Producto no encontrado.');
        }

        // Verificar restricciones de eliminación
        if ($producto->isRestricted()) { // Este es un ejemplo de una posible restricción
            throw new DeleteRestrictedException('Este recurso no puede eliminarse por restricciones.');
        }

        // Eliminar el producto
        $producto->delete();

        return $this->success(null, 204);
    }
}
