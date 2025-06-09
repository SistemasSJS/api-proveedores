<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Requests\Producto\ProductoStoreRequest;
use App\Http\Requests\Producto\ProductoUpdateLogoRequest;
use App\Http\Requests\Producto\ProductoUpdateRequest;
use App\Models\Proveedor;
use App\Http\Resources\ProductoResource;
use App\Models\Catalogo;
use App\Models\Producto;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Database\Factories\ProductoFactory;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(name="Productos", description="Gestión de catálogo")
 */
class ProductoCatalogoController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/api/proveedores/{proveedor}/Productoss/{id}/productos",
     *     summary="Obtener productos de un catálogo",
     *     tags={"Productos"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="proveedor", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", description="Buscar por nombre o código", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort_by", in="query", description="Campo para ordenar", @OA\Schema(type="string", example="nombre")),
     *     @OA\Parameter(name="order", in="query", description="asc o desc", @OA\Schema(type="string", enum={"asc", "desc"})),
     *     @OA\Response(response=200, description="Listado paginado de productos")
     * )
     */
    public function index(Request $request, Proveedor $proveedor, $id)
    {
        $catalogo = Catalogo::with(Catalogo::eagerLodable())->find($id);
        if (!$catalogo) {
            throw new ResourceNotFoundException("Producto no encontrado.");
        }

        $filters = $request->only(Producto::getFilters());
        $sortBy = $request->input('sort_by', 'nombre_comercial');
        $order = $request->input('order', 'asc');
        $perPage = $request->input('per_page', 10);

        $query = $catalogo->productos();

        $paginator = $query->with(Producto::eagerLodable())
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);

        $data = ProductoResource::collection($paginator)->resolve();

        return $this->paginated($paginator->setCollection(collect($data)));
    }



    public function show(Request $request, Proveedor $proveedor, $catalogoId, $id)
    {
        $catalogo = Catalogo::where('proveedor_id', $proveedor->id)->find($catalogoId);
        if (!$catalogo) {
            throw new ResourceNotFoundException("Catálogo no encontrado para este proveedor.");
        }

        $producto = $catalogo->productos()
            ->with(Producto::eagerLodable())
            ->find($id);
        if (!$producto) {
            throw new ResourceNotFoundException("Producto no encontrado en este catálogo.");
        }

        return $this->success(new ProductoResource($producto));
    }

    public function store(ProductoStoreRequest $request, Proveedor $proveedor, $catalogoId)
    {
        $data = $request->validated();
        $data['catalogo_id'] = $catalogoId;

        $producto = Producto::create($data);

        return $this->success(new ProductoResource($producto));
    }

    public function update(ProductoUpdateRequest $request, Proveedor $proveedor, $catalogoId, $id)
    {
        $producto = Producto::findOrFail($id);
        $producto->update($request->validated());
        return $this->success(new ProductoResource(($producto->fresh(Producto::eagerLodable()))));
    }

    public function updateLogo(ProductoUpdateLogoRequest $request, Proveedor $proveedor, $catalogoId, $id)
    {
        $catalogo = Catalogo::where('proveedor_id', $proveedor->id)->findOrFail($catalogoId);

        $producto = $catalogo->productos()->findOrFail($id);

        // Eliminar logo anterior si existe
        if ($producto->logo) {
            $rutaAnterior = str_replace(asset('storage') . '/', '', $producto->foto_url);
            Storage::disk('public')->delete($rutaAnterior);
        }

        // Guardar nuevo archivo
        $file = $request->file('logo');
        $filename = "logo_producto_{$producto->id}_" . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $filename, 'public');

        // Actualizar ruta en base de datos
        $producto->update(['logo' => $path]);

        return $this->success(new ProductoResource($producto->fresh(Producto::eagerLodable())));
    }
}
