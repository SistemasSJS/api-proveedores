<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Productos", description="CRUD de productos")
 */
class ProductoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/productos",
     *     summary="Listar todos los productos",
     *     tags={"Productos"},
     *     @OA\Response(response=200, description="Lista de productos", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Producto")))
     * )
     */
    public function index()
    {
        return Producto::with(['proveedor', 'sucursales'])->get();
    }

    /**
     * @OA\Post(
     *     path="/api/productos",
     *     summary="Crear un producto",
     *     tags={"Productos"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "proveedor_id"},
     *             @OA\Property(property="nombre", type="string", example="Producto A"),
     *             @OA\Property(property="email", type="string", example="contacto@producto.com"),
     *             @OA\Property(property="telefono", type="string", example="555-123-4567"),
     *             @OA\Property(property="direccion", type="string", example="Calle Falsa 123"),
     *             @OA\Property(property="proveedor_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Producto creado", @OA\JsonContent(ref="#/components/schemas/Producto"))
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'proveedor_id' => 'required|exists:proveedores,id',
        ]);

        $producto = Producto::create($request->all());

        return response()->json($producto->load(['proveedor', 'sucursales']), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/productos/{id}",
     *     summary="Obtener un producto",
     *     tags={"Productos"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Datos del producto", @OA\JsonContent(ref="#/components/schemas/Producto")),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function show($id)
    {
        $producto = Producto::with(['proveedor', 'sucursales'])->findOrFail($id);
        return response()->json($producto);
    }

    /**
     * @OA\Put(
     *     path="/api/productos/{id}",
     *     summary="Actualizar un producto",
     *     tags={"Productos"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string", example="Producto A"),
     *             @OA\Property(property="email", type="string", example="contacto@producto.com"),
     *             @OA\Property(property="telefono", type="string", example="555-123-4567"),
     *             @OA\Property(property="direccion", type="string", example="Calle Falsa 123"),
     *             @OA\Property(property="proveedor_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Producto actualizado", @OA\JsonContent(ref="#/components/schemas/Producto")),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function update(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);
        $producto->update($request->all());

        return response()->json($producto->load(['proveedor', 'sucursales']));
    }

    /**
     * @OA\Delete(
     *     path="/api/productos/{id}",
     *     summary="Eliminar un producto",
     *     tags={"Productos"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Producto eliminado"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->delete();

        return response()->json(null, 204);
    }
}
