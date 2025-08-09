<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Requests\Producto\ProductoStoreRequest;
use App\Http\Requests\Producto\ProductoUpdateLogoRequest;
use App\Http\Requests\Producto\ProductoUpdateRequest;
use App\Http\Requests\ProveedorImportProducto\ProveedorImportProductoRequest;
use App\Http\Requests\ProveedorImportProducto\ProductoBulkStoreJsonRequest;
use App\Http\Requests\ProveedorImportProducto\ProductoBulkStoreRequest;
use App\Models\Proveedor;
use App\Http\Resources\ProductoResource;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
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

        // ✅ Validar los datos del request
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


    public function bulkStore(ProveedorImportProductoRequest $request, Proveedor $proveedor)
    {
        $productosData = $request->validated()['productos'];
        $errores = [];

        // Cantidad de filas por lote
        $chunkSize = 500;

        // Contadores globales
        $productosCreados = [];
        $productosActualizados = [];
        $marcasCreadas = [];
        $marcasActualizadas = [];
        $categoriasCreadas = [];
        $categoriasActualizadas = [];
        $subcategoriasCreadas = [];
        $subcategoriasActualizadas = [];
        $unidadesCreadas = [];
        $unidadesActualizadas = [];

        foreach (array_chunk($productosData, $chunkSize) as $lote) {
            DB::beginTransaction();
            try {
                // 1. Pre-cargar datos existentes
                $nombresMarcas = collect($lote)->pluck('marca')->filter()->unique();
                $nombresCategorias = collect($lote)->pluck('categoria')->filter()->unique();
                $nombresUnidades = collect($lote)->pluck('unidad_medida')->filter()->unique();

                $marcasExistentes = Marca::where('proveedor_id', $proveedor->id)
                    ->whereIn('nombre', $nombresMarcas)
                    ->pluck('id', 'nombre');

                $categoriasExistentes = Categoria::where('proveedor_id', $proveedor->id)
                    ->whereIn('nombre', $nombresCategorias)
                    ->whereNull('parent_id')
                    ->pluck('id', 'nombre');

                $unidadesExistentes = UnidadMedida::where('proveedor_id', $proveedor->id)
                    ->whereIn('nombre', $nombresUnidades)
                    ->pluck('id', 'nombre');

                // 2. Crear las marcas que no existen
                foreach ($nombresMarcas as $nombre) {
                    if (!isset($marcasExistentes[$nombre])) {
                        $marca = Marca::create([
                            'nombre' => $nombre,
                            'proveedor_id' => $proveedor->id
                        ]);
                        $marcasExistentes[$nombre] = $marca->id;
                        $marcasCreadas[] = $marca;
                    } else {
                        $marcasActualizadas[] = $marcasExistentes[$nombre];
                    }
                }

                // 3. Crear categorías que no existen
                foreach ($nombresCategorias as $nombre) {
                    if (!isset($categoriasExistentes[$nombre])) {
                        $categoria = Categoria::create([
                            'nombre' => $nombre,
                            'proveedor_id' => $proveedor->id
                        ]);
                        $categoriasExistentes[$nombre] = $categoria->id;
                        $categoriasCreadas[] = $categoria;
                    } else {
                        $categoriasActualizadas[] = $categoriasExistentes[$nombre];
                    }
                }

                // 4. Crear unidades que no existen
                foreach ($nombresUnidades as $nombre) {
                    if (!isset($unidadesExistentes[$nombre])) {
                        $unidad = UnidadMedida::create([
                            'nombre' => $nombre,
                            'proveedor_id' => $proveedor->id
                        ]);
                        $unidadesExistentes[$nombre] = $unidad->id;
                        $unidadesCreadas[] = $unidad;
                    } else {
                        $unidadesActualizadas[] = $unidadesExistentes[$nombre];
                    }
                }

                // 5. Procesar subcategorías (necesitan categoría padre)
                foreach ($lote as $item) {
                    if (!empty($item['subcategoria']) && !empty($item['categoria'])) {
                        $parentId = $categoriasExistentes[$item['categoria']] ?? null;
                        if ($parentId) {
                            $subcategoria = Categoria::firstOrCreate(
                                [
                                    'nombre' => $item['subcategoria'],
                                    'parent_id' => $parentId,
                                    'proveedor_id' => $proveedor->id
                                ]
                            );
                            if ($subcategoria->wasRecentlyCreated) {
                                $subcategoriasCreadas[] = $subcategoria;
                            } else {
                                $subcategoriasActualizadas[] = $subcategoria;
                            }
                        }
                    }
                }

                // 6. Crear/actualizar productos
                foreach ($lote as $item) {
                    try {
                        $producto = Producto::updateOrCreate(
                            [
                                'codigo_interno' => $item['codigo'],
                                'proveedor_id' => $proveedor->id,
                            ],
                            [
                                'nombre' => $item['producto'],
                                'descripcion' => $item['descripcion'] ?? null,
                                'modelo' => $item['modelo'] ?? null,
                                'precio' => $item['precio'],
                                'precio_mayoreo' => $item['precio_mayoreo'] ?? null,
                                'precio_menuedeo' => $item['precio_menuedeo'] ?? null,
                                'marca_id' => $marcasExistentes[$item['marca']] ?? null,
                                'categoria_id' => $categoriasExistentes[$item['categoria']] ?? null,
                                'subcategoria_id' => isset($item['subcategoria'])
                                    ? Categoria::where('nombre', $item['subcategoria'])
                                    ->where('parent_id', $categoriasExistentes[$item['categoria']] ?? null)
                                    ->where('proveedor_id', $proveedor->id)
                                    ->value('id')
                                    : null,
                                'unidad_medida_id' => $unidadesExistentes[$item['unidad_medida']] ?? null,
                            ]
                        );

                        if ($producto->wasRecentlyCreated) {
                            $productosCreados[] = $producto;
                        } else {
                            $productosActualizados[] = $producto;
                        }
                    } catch (\Throwable $e) {
                        $errores[] = [
                            'item' => $item,
                            'error' => $e->getMessage()
                        ];
                    }
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                foreach ($lote as $item) {
                    $errores[] = [
                        'item' => $item,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return $this->success([
            'productos' => [
                'creados' => $productosCreados,
                'actualizados' => $productosActualizados,
            ],
            'marcas' => [
                'creados' => $marcasCreadas,
                'actualizados' => $marcasActualizadas,
            ],
            'categorias' => [
                'creados' => $categoriasCreadas,
                'actualizados' => $categoriasActualizadas,
            ],
            'subcategorias' => [
                'creados' => $subcategoriasCreadas,
                'actualizados' => $subcategoriasActualizadas,
            ],
            'unidades' => [
                'creados' => $unidadesCreadas,
                'actualizados' => $unidadesActualizadas,
            ],
            'errores' => $errores,
            'resumen' => [
                'total_intentos' => count($productosData),
                'exitosos' => count($productosCreados) + count($productosActualizados),
                'creados' => count($productosCreados),
                'actualizados' => count($productosActualizados),
                'fallidos' => count($errores),
            ]
        ], 'Proceso de carga masiva finalizado.');
    }
}
