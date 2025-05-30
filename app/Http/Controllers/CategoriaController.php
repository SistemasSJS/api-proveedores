<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

/**
 * @OA\Get(
 *     path="/api/categorias",
 *     summary="Listar todas las categorías con filtros opcionales y paginación",
 *     operationId="listarCategoria",
 *     tags={"Categoria"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="nombre", in="query", description="Filtrar por nombre.", @OA\Schema(type="string")),
 *     @OA\Parameter(name="estatus", in="query", description="Filtrar por estatus.", @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Listado paginado de categorías")
 * )
 */
class CategoriaController extends ControGgller
{
    /**gG
     * Listar todas las categorías con filtros opcionales y paginación
     */
    public function index(Request $request)
    {
        $filters = $request->only(['nombre', 'estatus']);
        $categorias = Categoria::filter($filters)->paginate(10);
        return $this->paginated($categorias);
    }

    /**
     * @OA\Post(
     *     path="/api/categorias",
     *     summary="Crear una categoría",
     *     operationId="crearCategoria",
     *     tags={"Categoria"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Categoría creada")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $categoria = Categoria::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($categoria, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/categorias/{id}",
     *     summary="Obtener una categoría por ID",
     *     operationId="mostrarCategoria",
     *     tags={"Categoria"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Categoría encontrada")
     * )
     */
    public function show($id)
    {
        $categoria = Categoria::findOrFail($id);
        return $this->success($categoria);
    }

    /**
     * @OA\Put(
     *     path="/api/categorias/{id}",
     *     summary="Actualizar categoría",
     *     operationId="actualizarCategoria",
     *     tags={"Categoria"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Categoría actualizada")
     * )
     */
    public function update(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $categoria->update($request->only(['nombre']));

        return $this->success($categoria);
    }

    /**
     * @OA\Delete(
     *     path="/api/categorias/{id}",
     *     summary="Eliminar categoría",
     *     operationId="eliminarCategoria",
     *     tags={"Categoria"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Categoría eliminada")
     * )
     */
    public function destroy($id)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->delete();

        return $this->success(null, 204);
    }
}
