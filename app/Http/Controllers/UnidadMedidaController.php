<?php

namespace App\Http\Controllers;

use App\Http\Resources\UnidadMedidaResource;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;

class UnidadMedidaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/unidades-medida",
     *     summary="Listar todas las unidades de medida con filtros opcionales y paginación",
     *     operationId="listarUnidadesMedida",
     *     tags={"UnidadMedida"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="nombre", in="query", description="Filtrar por nombre", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", description="Número de página", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Listado paginado de unidades de medida")
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only(UnidadMedida::getFilters());
        $originalPaginator = UnidadMedida::filter($filters)->paginate(1000);
        $unidadMedida = UnidadMedidaResource::collection($originalPaginator)->resolve();
        return $this->paginated($originalPaginator->setCollection(collect($unidadMedida)));
    }

    /**
     * @OA\Post(
     *     path="/api/unidades-medida",
     *     summary="Crear una unidad de medida",
     *     operationId="crearUnidadMedida",
     *     tags={"UnidadMedida"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Unidad de medida creada")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $unidadMedida = UnidadMedida::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($unidadMedida, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/unidades-medida/{id}",
     *     summary="Obtener una unidad de medida por ID",
     *     operationId="mostrarUnidadMedida",
     *     tags={"UnidadMedida"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Unidad de medida encontrada")
     * )
     */
    public function show($id)
    {
        $unidadMedida = UnidadMedida::findOrFail($id);
        return $this->success($unidadMedida);
    }

    /**
     * @OA\Put(
     *     path="/api/unidades-medida/{id}",
     *     summary="Actualizar unidad de medida",
     *     operationId="actualizarUnidadMedida",
     *     tags={"UnidadMedida"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Unidad de medida actualizada")
     * )
     */
    public function update(Request $request, $id)
    {
        $unidadMedida = UnidadMedida::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $unidadMedida->update($request->only(['nombre']));

        return $this->success($unidadMedida);
    }

    /**
     * @OA\Delete(
     *     path="/api/unidades-medida/{id}",
     *     summary="Eliminar unidad de medida",
     *     operationId="eliminarUnidadMedida",
     *     tags={"UnidadMedida"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Unidad de medida eliminada")
     * )
     */
    public function destroy($id)
    {
        $unidadMedida = UnidadMedida::findOrFail($id);
        $unidadMedida->delete();

        return $this->success(null, 204);
    }
}
