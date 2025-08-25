<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Crud\ResourceNotFoundException;

use App\Http\Requests\Producto\ProductoStoreRequest;
use App\Http\Requests\Producto\ProductoUpdateLogoRequest;
use App\Http\Requests\Producto\ProductoUpdateRequest;
use App\Models\Proveedor;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Storage;

class ProveedorProductoController extends Controller
{
    use ApiResponse;


    public function __construct() {}

    public function index(Request $request, Proveedor $proveedor)
    {
        /**
         * NOTE: para los filtros se debe revisar el metodo getFilters() 
         * y verifiacar  que exiata el scope para el filtro   
         *  - categoria_id
         *  - marca_id
         *  
         * Para este caso seria asi: ?categoria_id=3,7&marca_id=1
         */
        $filters = $request->only(Producto::getFilters());

        $sortBy = $request->input('sort_by', 'nombre');
        $order = $request->input('order', 'asc');
        $perPage = $request->input('per_page', 10);

        $paginator = Producto::query()
            ->filter($filters)
            ->where('proveedor_id', $proveedor->id)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);

        return $this->paginated($paginator);
        // $data = ProductoResource::collection($paginator)->resolve();
        // return $paginator->setCollection(collect($data)));
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

        // ✅ Validar los datos del request,
        $data = $request->validated();
        $data['proveedor_id'] = $proveedor->id;

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
