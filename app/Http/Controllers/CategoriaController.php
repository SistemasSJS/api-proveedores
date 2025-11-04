<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categorias",
     *     summary="Listar categorías",
     *     tags={"Categorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="nombre", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="estatus", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de categorías",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Categoria")),
     *             @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only(['nombre', 'estatus']);
        $categorias = Categoria::filter($filters)->paginate();

        return $this->paginated($categorias);
    }

    /**
     * @OA\Post(
     *     path="/api/categorias",
     *     summary="Crear nueva categoría",
     *     tags={"Categorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string", example="Materiales de Construcción")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Categoría creada"),
     *     @OA\Response(response=422, description="Error de validación")
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
     *     summary="Obtener categoría por ID",
     *     tags={"Categorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Datos de la categoría", @OA\JsonContent(ref="#/components/schemas/Categoria")),
     *     @OA\Response(response=404, description="Categoría no encontrada")
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
     *     tags={"Categorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Categoría actualizada"),
     *     @OA\Response(response=404, description="Categoría no encontrada")
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
     *     tags={"Categorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Categoría eliminada"),
     *     @OA\Response(response=404, description="Categoría no encontrada")
     * )
     */
    public function destroy($id)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->delete();

        return $this->success(null, 204);
    }
}
