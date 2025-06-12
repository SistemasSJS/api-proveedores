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
class ProductoCategoriaController extends Controller
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
    public function index(Request $request, Proveedor $proveedor)
    {
        $filters = $request->only(Producto::getFilters());
        $sortBy = $request->input('sort_by', 'nombre_comercial');
        $order = $request->input('order', 'asc');
        $perPage = $request->input('per_page', 10);


        $paginator = Producto::with(Producto::eagerLodable())
            ->filter($filters)
            ->where('proveedor_id', $proveedor->id)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);
        $data = ProductoResource::collection($paginator)->resolve();

        return $this->paginated($paginator->setCollection(collect($data)));
    }



    public function show(Request $request, Proveedor $proveedor, $productoId)
    {
        $producto = Producto::with(Producto::eagerLodable())->findOrFail($productoId);


        if ($producto->proveedor_id !== $proveedor->id) {
            throw new ResourceNotFoundException("Producto no relacionado al proveedor.");
        }

        return $this->success(new ProductoResource($producto));
    }

    public function store(ProductoStoreRequest $request, Proveedor $proveedor, $productoId)
    {
        $data = $request->validated();
        $data['proveedor'] = $proveedor->id;

        $producto = Producto::create($data);

        return $this->success(new ProductoResource($producto));
    }

    public function update(ProductoUpdateRequest  $request, Proveedor $proveedor, $productoId)
    {
        $producto = Producto::findOrFail($productoId);
        $producto->update($request->validated());
        return $this->success(new ProductoResource(($producto->fresh(Producto::eagerLodable()))));
    }

    public function updateLogo(ProductoUpdateLogoRequest $request, Proveedor $proveedor, $productoId)
    {
        $producto = Producto::findOrFail($productoId);
        // Eliminar logo anterior si existe
        if ($producto->imagen_principal) {
            $rutaAnterior = str_replace(asset('storage') . '/', '', $producto->imagen_principal);
            Storage::disk('public')->delete($rutaAnterior);
        }

        // Guardar nuevo archivo
        $file = $request->file('logo');
        $filename = "logo_producto_{$producto->id}_" . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $filename, 'public');

        // Actualizar ruta en base de datos
        $producto->update(['imagen_principal' => $path]);

        return $this->success(new ProductoResource($producto->fresh(Producto::eagerLodable())));
    }
}
