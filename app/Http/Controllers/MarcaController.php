<?php

namespace App\Http\Controllers;

use App\Http\Resources\MarcaResource;
use App\Models\Marca;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/marcas",
     *     summary="Listar todas las marcas con filtros opcionales y paginación",
     *     operationId="listarMarca",
     *     tags={"Marca"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="nombre", in="query", description="Filtrar por nombre.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="estatus", in="query", description="Filtrar por estatus.", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Listado paginado de marcas")
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only(Marca::getFilters());
        $originalPaginator = Marca::filter($filters)->paginate(1000);
        $marcas = MarcaResource::collection($originalPaginator)->resolve();
        return $this->paginated($originalPaginator->setCollection(collect($marcas)));
    }

    /**
     * @OA\Post(
     *     path="/api/marcas",
     *     summary="Crear una marca",
     *     operationId="crearMarca",
     *     tags={"Marca"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="marca creada")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $Marca = Marca::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($Marca, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/marcas/{id}",
     *     summary="Obtener una marca por ID",
     *     operationId="mostrarMarca",
     *     tags={"Marca"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="marca encontrada")
     * )
     */
    public function show($id)
    {
        $Marca = Marca::findOrFail($id);
        return $this->success($Marca);
    }

    /**
     * @OA\Put(
     *     path="/api/marcas/{id}",
     *     summary="Actualizar marca",
     *     operationId="actualizarMarca",
     *     tags={"Marca"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="marca actualizada")
     * )
     */
    public function update(Request $request, $id)
    {
        $Marca = Marca::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $Marca->update($request->only(['nombre']));

        return $this->success($Marca);
    }

    /**
     * @OA\Delete(
     *     path="/api/marcas/{id}",
     *     summary="Eliminar marca",
     *     operationId="eliminarMarca",
     *     tags={"Marca"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="marca eliminada")
     * )
     */
    public function destroy($id)
    {
        $Marca = Marca::findOrFail($id);
        $Marca->delete();

        return $this->success(null, 204);
    }
}
