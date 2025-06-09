<?php

namespace App\Http\Controllers;

use App\Http\Resources\LineaResource;
use App\Models\Linea;
use Illuminate\Http\Request;

class LineaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/lineas",
     *     summary="Listar todas las líneas con filtros opcionales y paginación",
     *     operationId="listarLinea",
     *     tags={"Linea"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="nombre", in="query", description="Filtrar por nombre.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="estatus", in="query", description="Filtrar por estatus.", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Listado paginado de líneas")
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only(Linea::getFilters());
        $originalPaginator = Linea::filter($filters)->paginate(1000);
        $lineas = LineaResource::collection($originalPaginator)->resolve();
        return $this->paginated($originalPaginator->setCollection(collect($lineas)));
    }

    /**
     * @OA\Post(
     *     path="/api/lineas",
     *     summary="Crear una línea",
     *     operationId="crearLinea",
     *     tags={"Linea"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Línea creada")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $linea = Linea::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($linea, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/lineas/{id}",
     *     summary="Obtener una línea por ID",
     *     operationId="mostrarLinea",
     *     tags={"Linea"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Línea encontrada")
     * )
     */
    public function show($id)
    {
        $linea = Linea::findOrFail($id);
        return $this->success($linea);
    }

    /**
     * @OA\Put(
     *     path="/api/lineas/{id}",
     *     summary="Actualizar línea",
     *     operationId="actualizarLinea",
     *     tags={"Linea"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Línea actualizada")
     * )
     */
    public function update(Request $request, $id)
    {
        $linea = Linea::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $linea->update($request->only(['nombre']));

        return $this->success($linea);
    }

    /**
     * @OA\Delete(
     *     path="/api/lineas/{id}",
     *     summary="Eliminar línea",
     *     operationId="eliminarLinea",
     *     tags={"Linea"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Línea eliminada")
     * )
     */
    public function destroy($id)
    {
        $linea = Linea::findOrFail($id);
        $linea->delete();

        return $this->success(null, 204);
    }
}
