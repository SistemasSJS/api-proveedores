<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Requests\Producto\ProductoStoreRequest;
use App\Http\Requests\Producto\ProductoUpdateLogoRequest;
use App\Http\Requests\Producto\ProductoUpdateRequest;
use App\Http\Requests\ProductoImport\ProductoBulkStoreRequest;
use App\Models\Proveedor;
use App\Http\Resources\ProductoResource;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Storage;

class ProveedorProductoController extends Controller
{
    use ApiResponse;

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

        $sortBy = $request->input('sort_by', 'nombre_comercial');
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

    public function bulkStore(ProductoBulkStoreRequest $request, Proveedor $proveedor)
    {
        $productosData = $request->validated()['productos'];
        $productosCreados = [];
        $errores = [];

        foreach ($productosData as $index => $item) {
            try {
                // Buscar o crear relaciones (por nombre + proveedor)
                $marca = isset($item['marca'])
                    ? Marca::firstOrCreate(
                        ['nombre' => $item['marca'], 'proveedor_id' => $proveedor->id]
                    )
                    : null;

                $categoria = isset($item['categoria'])
                    ? Categoria::firstOrCreate(
                        ['nombre' => $item['categoria'], 'proveedor_id' => $proveedor->id]
                    )
                    : null;

                $unidad = isset($item['unidad_medida'])
                    ? UnidadMedida::firstOrCreate(
                        ['nombre' => $item['unidad_medida'], 'proveedor_id' => $proveedor->id]
                    )
                    : null;

                $subcategoria = null;
                if ($categoria && isset($item['subcategoria'])) {
                    $subcategoria = Categoria::firstOrCreate(
                        [
                            'nombre' => $item['subcategoria'],
                            'categoria_id' => $categoria->id,
                            'proveedor_id' => $proveedor->id,
                        ]
                    );
                }

                // Crear producto
                $producto = Producto::create([
                    'codigo' => $item['codigo'],
                    'nombre_comercial' => $item['producto'],
                    'descripcion' => $item['descripcion'] ?? null,
                    'modelo' => $item['modelo'] ?? null,
                    'precio' => $item['precio'],
                    'precio_mayoreo' => $item['precio_mayoreo'] ?? null,
                    'precio_menuedeo' => $item['precio_menuedeo'] ?? null,
                    'marca_id' => $marca?->id,
                    'categoria_id' => $categoria?->id,
                    'subcategoria_id' => $subcategoria?->id,
                    'unidad_medida_id' => $unidad?->id,
                    'proveedor_id' => $proveedor->id,
                ]);

                $productosCreados[] = new ProductoResource($producto->fresh(Producto::eagerLodable()));
            } catch (\Throwable $e) {
                $errores[] = [
                    'item' => $item,
                    'error' => $e->getMessage(),
                ];
                report($e); // Para que quede en logs
                continue;
            }
        }

        return $this->success([
            'productos_creados' => $productosCreados,
            'errores' => $errores,
            'resumen' => [
                'total_intentos' => count($productosData),
                'exitosos' => count($productosCreados),
                'fallidos' => count($errores),
            ]
        ], 'Proceso de carga masiva finalizado.');
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
        // ✅ Retornar el recurso con relaciones cargadas
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
