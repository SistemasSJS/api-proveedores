<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Models\Catalogo;
use App\Models\Proveedor;
use App\Http\Requests\Catalogo\CatalogoStoreRequest;
use App\Http\Requests\Catalogo\CatalogoUpdateRequest;
use App\Http\Resources\CatalogoResource;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
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
     *     path="/api/proveedores/{proveedor}/catalogos",
     *     summary="Listar catálogos",
     *     tags={"Catalogo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="proveedor", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="sort_by", in="query", description="Campo para ordenar", required=false, @OA\Schema(type="string", example="nombre")),
     *     @OA\Parameter(name="order", in="query", description="Dirección de ordenamiento", required=false, @OA\Schema(type="string", enum={"asc", "desc"}, example="asc")),
     *     @OA\Response(response=200, description="Listado paginado de catálogos", @OA\JsonContent(ref="#/components/schemas/ApiPaginatedResponse"))
     * )
     */
    public function index(Request $request, Proveedor $proveedor)
    {
        $filters = $request->only(Catalogo::getFilters());
        $sortBy = $request->input('sort_by', 'nombre');
        $order = $request->input('order', 'asc');

        $query = Catalogo::with(Catalogo::eagerLodable())
            ->filter($filters)
            ->where('proveedor_id', $proveedor->id);

        $originalPaginator = $query->orderBy($sortBy, $order)->paginate(10);
        $data = CatalogoResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    /**
     * @OA\Post(
     *     path="/api/proveedores/{proveedor}/catalogos",
     *     summary="Crear un ítem de catálogo",
     *     tags={"Catalogo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="proveedor", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CatalogoStoreRequest")),
     *     @OA\Response(response=201, description="Ítem creado")
     * )
     */
    public function store(CatalogoStoreRequest $request, Proveedor $proveedor)
    {
        $data = $request->validated();
        $data['proveedor_id'] = $proveedor->id;

        $catalogo = Catalogo::create($data);

        return $this->success(new CatalogoResource($catalogo), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/{proveedor}/catalogos/{id}",
     *     summary="Mostrar un ítem del catálogo por ID",
     *     tags={"Catalogo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="proveedor", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Ítem encontrado")
     * )
     */
    public function show(Request $request, Proveedor $proveedor, $id)
    {
        $catalogo = Catalogo::with(Catalogo::eagerLodable())->findOrFail($id);

        if ($catalogo->proveedor_id !== $proveedor->id) {
            abort(403, 'No autorizado para ver este catálogo.');
        }

        return $this->success(new CatalogoResource($catalogo));
    }

    /**
     * @OA\Put(
     *     path="/api/proveedores/{proveedor}/catalogos/{id}",
     *     summary="Actualizar un ítem del catálogo",
     *     tags={"Catalogo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="proveedor", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/CatalogoUpdateRequest")),
     *     @OA\Response(response=200, description="Ítem actualizado")
     * )
     */
    public function update(CatalogoUpdateRequest $request, Proveedor $proveedor, $id)
    {
        $catalogo = Catalogo::findOrFail($id);

        if ($catalogo->proveedor_id !== $proveedor->id) {
            abort(403, 'No autorizado para actualizar este catálogo.');
        }

        $catalogo->update($request->validated());

        return $this->success(new CatalogoResource($catalogo));
    }

    /**
     * @OA\Delete(
     *     path="/api/proveedores/{proveedor}/catalogos/{id}",
     *     summary="Eliminar un ítem del catálogo",
     *     tags={"Catalogo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="proveedor", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Ítem eliminado")
     * )
     */
    public function destroy(Request $request, Proveedor $proveedor, $id)
    {
        $catalogo = Catalogo::findOrFail($id);

        if ($catalogo->proveedor_id !== $proveedor->id) {
            abort(403, 'No autorizado para eliminar este catálogo.');
        }

        $catalogo->delete();

        return $this->success(null, 204);
    }


    /**
     * @OA\Get(
     *     path="/api/proveedores/{proveedor}/catalogos/{id}/productos",
     *     summary="Obtener productos de un catálogo",
     *     tags={"Catalogo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="proveedor", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", description="Buscar por nombre o código", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort_by", in="query", description="Campo para ordenar", @OA\Schema(type="string", example="nombre")),
     *     @OA\Parameter(name="order", in="query", description="asc o desc", @OA\Schema(type="string", enum={"asc", "desc"})),
     *     @OA\Response(response=200, description="Listado paginado de productos")
     * )
     */
    public function productos(Request $request, Proveedor $proveedor, $id)
    {
        $catalogo = Catalogo::with(Catalogo::eagerLodable())->find($id);
        if (!$catalogo) {
            throw new ResourceNotFoundException("Producto no encontrado.");
        }
        $query = $catalogo->productos()->with(Producto::eagerLodable());

        $filters = $request->only(Producto::getFilters());
        $sortBy = $request->input('sort_by', 'nombre_comercial');
        $order = $request->input('order', 'asc');

        $paginator = $query->filter($filters)->orderBy($sortBy, $order)->paginate(10);
        $data = ProductoResource::collection($paginator)->resolve();

        return $this->paginated($paginator->setCollection(collect($data)));
    }
}
