<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Requests\Producto\ProductoStoreRequest;
use App\Http\Requests\Producto\ProductoUpdateLogoRequest;
use App\Http\Requests\Producto\ProductoUpdateRequest;
use App\Models\Proveedor;
use App\Http\Resources\ProductoResource;
use App\Models\Catalogo;
use App\Models\Linea;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Database\Factories\ProductoFactory;
use Illuminate\Support\Facades\Storage;

class ProveedorProductoController extends Controller
{
    use ApiResponse;

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

    public function store(ProductoStoreRequest $request, Proveedor $proveedor)
    {
        // ✅ Verificar que el producto pertenezca al proveedor
        // if ($producto->proveedor_id !== $proveedor->id) {
        //     return $this->error('El producto no pertenece a este proveedor.', 403);
        // }

        // ✅ Validar los datos del request
        $data = $request->validated();
        $data['proveedor_id'] = $proveedor->id;

        $producto = Producto::create($data);

        // ✅ Sincronizar categorías si existen en el request
        if (isset($data['categorias']) && is_array($data['categorias'])) {
            $producto->categorias()->sync($data['categorias']);
        }

        // ✅ Sincronizar especificaciones si existen en el request
        if (isset($data['especificaciones']) && is_array($data['especificaciones'])) {
            $producto->especificaciones()->sync($data['especificaciones']);
        }

        // ✅ Retornar el recurso actualizado
        return $this->success(new ProductoResource($producto->fresh(Producto::eagerLodable())));
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
        if ($producto->imagen_principal) {
            $rutaAnterior = str_replace(asset('storage') . '/', '', $producto->imagen_principal);
            Storage::disk('public')->delete($rutaAnterior);
        }

        $file = $request->file('logo');
        $filename = "logo_producto_{$producto->id}_" . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $filename, 'public');

        $producto->update(['imagen_principal' => $path]);
        return $this->success(new ProductoResource($producto->fresh(Producto::eagerLodable())));
    }

    public function destroy(Request $request, Proveedor $proveedor, $productoId)
    {
        $producto = Producto::findOrFail($productoId);
        // $producto->sucursales()->detach();
        $producto->delete();
        return $this->success(message: "Producto eliminado correctamente.");
    }
}
