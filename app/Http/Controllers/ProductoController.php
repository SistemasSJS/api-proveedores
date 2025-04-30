<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Exceptions\Api\Crud\InvalidInputException;
use App\Exceptions\Api\Crud\DeleteRestrictedException;
use App\Exceptions\Api\Crud\ConflictException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(name="Productos", description="CRUD de productos")
 */
class ProductoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/productos",
     *     summary="Listar productos",
     *     operationId="getProductos",
     *     tags={"Producto"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de productos",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Producto"))
     *     )
     * )
     */
    public function index()
    {
        return Producto::with(["unidad_medida", "grupo", "imagenes", "proveedor"])->get();
    }

    /**
     * @OA\Post(
     *     path="/api/productos",
     *     summary="Crear un nuevo producto",
     *     operationId="storeProducto",
     *     tags={"Producto"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Producto")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Producto creado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Producto")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Entrada inválida",
     *         @OA\JsonContent(ref="#/components/schemas/InvalidInputException")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflicto con el estado actual del recurso",
     *         @OA\JsonContent(ref="#/components/schemas/ConflictException")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
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
            'grupo_id' => 'required|exists:grupos,id',
        ]);

        $producto = Producto::create($request->all());

        return response()->json($producto->load(["unidad_medida", "grupo", "imagenes", "proveedor"]), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/productos/{id}",
     *     summary="Obtener un producto específico",
     *     operationId="getProducto",
     *     tags={"Producto"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del producto",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/Producto")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Producto no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ResourceNotFoundException")
     *     )
     * )
     */
    public function show($id)
    {
        // Intentar encontrar el producto, si no se encuentra lanzar ResourceNotFoundException
        $producto = Producto::with(["unidad_medida", "grupo", "imagenes", "proveedor"])->find($id);
        if (!$producto) {
            throw new ResourceNotFoundException("Producto no encontrado.");
        }
        return response()->json($producto);
    }

    /**
     * @OA\Put(
     *     path="/api/productos/{id}",
     *     summary="Actualizar un producto",
     *     operationId="updateProducto",
     *     tags={"Producto"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del producto",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Producto")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto actualizado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Producto")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Entrada inválida",
     *         @OA\JsonContent(ref="#/components/schemas/InvalidInputException")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflicto con el estado actual del recurso",
     *         @OA\JsonContent(ref="#/components/schemas/ConflictException")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Producto no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ResourceNotFoundException")
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        // Verificar que el producto exista
        $producto = Producto::find($id);
        if (!$producto) {
            throw new ResourceNotFoundException("Producto no encontrado.");
        }

        // Actualizar el producto
        $producto->update($request->all());

        return response()->json($producto->load(["unidad_medida", "grupo", "imagenes", "proveedor"]), 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/productos/{id}",
     *     summary="Eliminar un producto",
     *     operationId="deleteProducto",
     *     tags={"Producto"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del producto",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto eliminado exitosamente"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No se puede eliminar este producto por restricciones",
     *         @OA\JsonContent(ref="#/components/schemas/DeleteRestrictedException")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Producto no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ResourceNotFoundException")
     *     )
     * )
     */    public function destroy($id)
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

        return response()->json(null, 204);
    }
}
