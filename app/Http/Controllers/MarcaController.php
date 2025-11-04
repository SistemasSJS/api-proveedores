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
     *     summary="Listar marcas",
     *     tags={"Marcas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de marcas",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
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
     *     summary="Crear nueva marca",
     *     tags={"Marcas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string", example="DeWalt")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Marca creada")
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
     *     summary="Obtener marca por ID",
     *     tags={"Marcas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Datos de la marca"),
     *     @OA\Response(response=404, description="Marca no encontrada")
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
     *     tags={"Marcas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Marca actualizada")
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
     *     tags={"Marcas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Marca eliminada")
     * )
     */
    public function destroy($id)
    {
        $Marca = Marca::findOrFail($id);
        $Marca->delete();

        return $this->success(null, 204);
    }
}
