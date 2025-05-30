<?php

namespace App\Http\Controllers;

use App\Models\Catalogo;
use App\Http\Requests\Catalogo\CatalogoStoreRequest;
use App\Http\Requests\Catalogo\CatalogoUpdateRequest;
use App\Http\Resources\CatalogoResource;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

/**
 * @OA\Tag(name="Catalogo", description="Gestión de catálogo")
 */
class CatalogoController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/api/catalogos",
     *     summary="Listar catálogos",
     *     tags={"Catalogo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Campo para ordenar",
     *         required=false,
     *         @OA\Schema(type="string", example="nombre")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Dirección de ordenamiento",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="asc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listado paginado de catálogos",
     *         @OA\JsonContent(ref="#/components/schemas/ApiPaginatedResponse")
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->only(Catalogo::getFilters());

        $sortBy = $request->input('sort_by', 'nombre');
        $order = $request->input('order', 'asc');

        $query = Catalogo::with(Catalogo::eagerLodable())->filter($filters);

        if ($user->role->nombre === 'PROVEEDOR') {
            $query->where('proveedor_id', $user->mainProveedor()->first()->id);
        }

        $originalPaginator = $query->orderBy($sortBy, $order)->paginate(10);
        $data = CatalogoResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    /**
     * @OA\Post(
     *     path="/api/catalogos",
     *     summary="Crear un ítem de catálogo",
     *     tags={"Catalogo"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CatalogoStoreRequest")
     *     ),
     *     @OA\Response(response=201, description="Ítem creado")
     * )
     */
    public function store(CatalogoStoreRequest $request)
    {
        $data = $request->validated();

        // Si es proveedor, forzamos el proveedor_id
        if ($request->user()->role->nombre === 'PROVEEDOR') {
            $data['proveedor_id'] = $request->user()->mainProveedor()->first()->id;
        }

        $catalogo = Catalogo::create($data);

        return $this->success(new CatalogoResource($catalogo), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/catalogos/{id}",
     *     summary="Mostrar un ítem del catálogo por ID",
     *     tags={"Catalogo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Ítem encontrado")
     * )
     */
    public function show(Request $request, $id)
    {
        $catalogo = Catalogo::with(Catalogo::eagerLodable())->findOrFail($id);

        if (
            $request->user()->role->nombre === 'PROVEEDOR' &&
            $catalogo->proveedor_id !== $request->user()->mainProveedor()->first()->id
        ) {
            abort(403, 'No autorizado para ver este catálogo.');
        }

        return $this->success(new CatalogoResource($catalogo));
    }

    /**
     * @OA\Put(
     *     path="/api/catalogos/{id}",
     *     summary="Actualizar un ítem del catálogo",
     *     tags={"Catalogo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(ref="#/components/schemas/CatalogoUpdateRequest")
     *     ),
     *     @OA\Response(response=200, description="Ítem actualizado")
     * )
     */
    public function update(CatalogoUpdateRequest $request, $id)
    {
        $catalogo = Catalogo::findOrFail($id);

        if (
            $request->user()->role->nombre === 'PROVEEDOR' &&
            $catalogo->proveedor_id !== $request->user()->mainProveedor()->first()->id
        ) {
            abort(403, 'No autorizado para actualizar este catálogo.');
        }

        $catalogo->update($request->validated());

        return $this->success(new CatalogoResource($catalogo));
    }

    /**
     * @OA\Delete(
     *     path="/api/catalogos/{id}",
     *     summary="Eliminar un ítem del catálogo",
     *     tags={"Catalogo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Ítem eliminado")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $catalogo = Catalogo::findOrFail($id);

        if (
            $request->user()->role->nombre === 'PROVEEDOR' &&
            $catalogo->proveedor_id !== $request->user()->mainProveedor()->first()->id
        ) {
            abort(403, 'No autorizado para eliminar este catálogo.');
        }

        $catalogo->delete();

        return $this->success(null, 204);
    }
}
