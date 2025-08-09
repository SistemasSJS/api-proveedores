<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProveedorImportoProducto\ProveedorImportProductoRequest;
use App\Models\Marca;
use App\Models\Categoria;
use App\Models\UnidadMedida;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Http\Requests\ProveedorImportProducto\MarcaBulkStoreRequest;
use App\Http\Requests\ProveedorImportProducto\CategoriaBulkStoreRequest;
use App\Http\Requests\ProveedorImportProducto\ProductoBulkStoreJsonRequest;
use App\Http\Requests\ProveedorImportProducto\UnidadMedidaBulkStoreRequest;
use App\Http\Resources\ElementoImportadoResource;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class ProveedorImportProductController extends Controller
{
  use ApiResponse;

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

  public function bulkStoreJson(ProductoBulkStoreJsonRequest $request, Proveedor $proveedor)
  {
    $file = $request->file('file');

    // try {
    $jsonContent = file_get_contents($file->getRealPath());
    $productosData = json_decode($jsonContent, true);

    if (!is_array($productosData)) {
      return $this->error('El archivo JSON debe contener un array de productos.', 422);
    }
    $productosData = $request->validated()['file'];
    $errores = [];

    $productosCreados = [];
    $productosActualizados = [];

    $marcasCreadas = [];
    $marcasActualizadas = [];

    $subcategoriasCreadas = [];
    $subcategoriasActualizadas = [];

    $categoriasCreadas = [];
    $categoriasActualizadas = [];

    $unidadesCreadas = [];
    $unidadesActualizadas = [];

    foreach ($productosData as $index => $item) {
      try {
        // Buscar o crear relaciones (por nombre + proveedor)
        $marca = isset($item['marca'])
          ? Marca::firstOrCreate(
            ['nombre' => $item['marca'], 'proveedor_id' => $proveedor->id]
          )
          : null;
        if ($marca->wasRecentlyCreated) {
          $marcasCreadas[] = $marca;
        } else {
          $marcasActualizadas[] = $marca;
        }

        $categoria = isset($item['categoria'])
          ? Categoria::firstOrCreate(
            ['nombre' => $item['categoria'], 'proveedor_id' => $proveedor->id]
          )
          : null;
        if ($categoria->wasRecentlyCreated) {
          $categoriasCreadas[] = $categoria;
        } else {
          $categoriasActualizadas[] = $categoria;
        }

        $unidad = isset($item['unidad_medida'])
          ? UnidadMedida::firstOrCreate(
            ['nombre' => $item['unidad_medida'], 'proveedor_id' => $proveedor->id]
          )
          : null;
        if ($unidad->wasRecentlyCreated) {
          $unidadesCreadas[] = $unidad;
        } else {
          $unidadesActualizadas[] = $unidad;
        }

        $subcategoria = null;
        if ($categoria && isset($item['subcategoria'])) {
          $subcategoria = Categoria::firstOrCreate(
            [
              'nombre' => $item['subcategoria'],
              'parent_id' => $categoria->id,
              'proveedor_id' => $proveedor->id,
            ]
          );
        }
        if ($subcategoria->wasRecentlyCreated) {
          $subcategoriasCreadas[] = $subcategoria;
        } else {
          $subcategoriasActualizadas[] = $subcategoria;
        }

        // Crear o actualizar producto por código + proveedor_id
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
            'marca_id' => $marca?->id,
            'categoria_id' => $categoria?->id,
            'subcategoria_id' => $subcategoria?->id,
            'unidad_medida_id' => $unidad?->id,
          ]
        );

        $productoResource = new ProductoResource($producto->fresh(Producto::eagerLodable()));

        if ($producto->wasRecentlyCreated) {
          $productosCreados[] = $productoResource;
        } else {
          $productosActualizados[] = $productoResource;
        }
      } catch (\Throwable $e) {
        $errores[] = [
          'item' => $item,
          'error' => $e->getMessage(),
        ];
        report($e);
        continue;
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

  public function storeMarcas(MarcaBulkStoreRequest $request, Proveedor $proveedor)
  {
    $data = $request->validated()['marcas'];
    $creadas = [];
    $actualizadas = [];

    foreach ($data as $marcaData) {
      $marca = Marca::firstOrCreate(
        ['nombre' => $marcaData['nombre'], 'proveedor_id' => $proveedor->id],
        ['descripcion' => $marcaData['descripcion'] ?? null]
      );

      if ($marca->wasRecentlyCreated) {
        $creadas[] = $marca;
      } else {
        $actualizadas[] = $marca;
      }
    }

    return $this->success([
      'creadas' => ElementoImportadoResource::collection($creadas),
      'actualizadas' => ElementoImportadoResource::collection($actualizadas),
    ], 'Marcas procesadas correctamente.');
  }

  public function storeCategorias(CategoriaBulkStoreRequest $request, Proveedor $proveedor)
  {
    $data = $request->validated()['categorias'];
    $creadas = [];
    $actualizadas = [];
    $subcreadas = [];
    $subactualizadas = [];

    foreach ($data as $catData) {
      $categoria = Categoria::firstOrCreate(
        ['nombre' => $catData['nombre'], 'proveedor_id' => $proveedor->id],
        ['descripcion' => $catData['descripcion'] ?? null]
      );

      if ($categoria->wasRecentlyCreated) {
        $creadas[] = $categoria;
      } else {
        $actualizadas[] = $categoria;
      }

      if (!empty($catData['subcategoria'])) {
        $subcategoria = Categoria::firstOrCreate(
          [
            'nombre' => $catData['subcategoria'],
            'parent_id' => $categoria->id,
            'proveedor_id' => $proveedor->id
          ]
        );

        if ($subcategoria->wasRecentlyCreated) {
          $subcreadas[] = $subcategoria;
        } else {
          $subactualizadas[] = $subcategoria;
        }
      }
    }

    return $this->success([
      'categorias' => [
        'creadas' => ElementoImportadoResource::collection($creadas),
        'actualizadas' => ElementoImportadoResource::collection($actualizadas),
      ],
      'subcategorias' => [
        'creadas' => ElementoImportadoResource::collection($subcreadas),
        'actualizadas' => ElementoImportadoResource::collection($subactualizadas),
      ]
    ], 'Categorías y subcategorías procesadas correctamente.');
  }

  public function storeUnidades(UnidadMedidaBulkStoreRequest $request, Proveedor $proveedor)
  {
    $data = $request->validated()['unidades'];
    $creadas = [];
    $actualizadas = [];

    foreach ($data as $unidadData) {
      $unidad = UnidadMedida::firstOrCreate(
        ['nombre' => $unidadData['nombre'], 'proveedor_id' => $proveedor->id],
        [
          'clave' => $unidadData['clave'] ?? null,
          'descripcion' => $unidadData['descripcion'] ?? null
        ]
      );

      if ($unidad->wasRecentlyCreated) {
        $creadas[] = $unidad;
      } else {
        $actualizadas[] = $unidad;
      }
    }

    return $this->success([
      'creadas' => ElementoImportadoResource::collection($creadas),
      'actualizadas' => ElementoImportadoResource::collection($actualizadas),
    ], 'Unidades de medida procesadas correctamente.');
  }


  // public function old_bulkStore(ProductoBulkStoreRequest $request, Proveedor $proveedor)
  // {
  //   $productosData = $request->validated()['productos'];
  //   $errores = [];

  //   $productosCreados = [];
  //   $productosActualizados = [];

  //   $marcasCreadas = [];
  //   $marcasActualizadas = [];

  //   $subcategoriasCreadas = [];
  //   $subcategoriasActualizadas = [];

  //   $categoriasCreadas = [];
  //   $categoriasActualizadas = [];

  //   $unidadesCreadas = [];
  //   $unidadesActualizadas = [];

  //   foreach ($productosData as $index => $item) {
  //     try {
  //       // Buscar o crear relaciones (por nombre + proveedor)
  //       $marca = isset($item['marca'])
  //         ? Marca::firstOrCreate(
  //           ['nombre' => $item['marca'], 'proveedor_id' => $proveedor->id]
  //         )
  //         : null;
  //       if ($marca->wasRecentlyCreated) {
  //         $marcasCreadas[] = $marca;
  //       } else {
  //         $marcasActualizadas[] = $marca;
  //       }

  //       $categoria = isset($item['categoria'])
  //         ? Categoria::firstOrCreate(
  //           ['nombre' => $item['categoria'], 'proveedor_id' => $proveedor->id]
  //         )
  //         : null;
  //       if ($categoria->wasRecentlyCreated) {
  //         $categoriasCreadas[] = $categoria;
  //       } else {
  //         $categoriasActualizadas[] = $categoria;
  //       }

  //       $unidad = isset($item['unidad_medida'])
  //         ? UnidadMedida::firstOrCreate(
  //           ['nombre' => $item['unidad_medida'], 'proveedor_id' => $proveedor->id]
  //         )
  //         : null;
  //       if ($unidad->wasRecentlyCreated) {
  //         $unidadesCreadas[] = $unidad;
  //       } else {
  //         $unidadesActualizadas[] = $unidad;
  //       }

  //       $subcategoria = null;
  //       if ($categoria && isset($item['subcategoria'])) {
  //         $subcategoria = Categoria::firstOrCreate(
  //           [
  //             'nombre' => $item['subcategoria'],
  //             'parent_id' => $categoria->id,
  //             'proveedor_id' => $proveedor->id,
  //           ]
  //         );
  //       }
  //       if ($subcategoria->wasRecentlyCreated) {
  //         $subcategoriasCreadas[] = $subcategoria;
  //       } else {
  //         $subcategoriasActualizadas[] = $subcategoria;
  //       }

  //       // Crear o actualizar producto por código + proveedor_id
  //       $producto = Producto::updateOrCreate(
  //         [
  //           'codigo_interno' => $item['codigo'],
  //           'proveedor_id' => $proveedor->id,
  //         ],
  //         [
  //           'nombre' => $item['producto'],
  //           'descripcion' => $item['descripcion'] ?? null,
  //           'modelo' => $item['modelo'] ?? null,
  //           'precio' => $item['precio'],
  //           'precio_mayoreo' => $item['precio_mayoreo'] ?? null,
  //           'precio_menuedeo' => $item['precio_menuedeo'] ?? null,
  //           'marca_id' => $marca?->id,
  //           'categoria_id' => $categoria?->id,
  //           'subcategoria_id' => $subcategoria?->id,
  //           'unidad_medida_id' => $unidad?->id,
  //         ]
  //       );

  //       $productoResource = new ProductoResource($producto->fresh(Producto::eagerLodable()));

  //       if ($producto->wasRecentlyCreated) {
  //         $productosCreados[] = $productoResource;
  //       } else {
  //         $productosActualizados[] = $productoResource;
  //       }
  //     } catch (\Throwable $e) {
  //       $errores[] = [
  //         'item' => $item,
  //         'error' => $e->getMessage(),
  //       ];
  //       report($e);
  //       continue;
  //     }
  //   }

  //   return $this->success([
  //     'productos' => [
  //       'creados' => $productosCreados,
  //       'actualizados' => $productosActualizados,
  //     ],
  //     'marcas' => [
  //       'creados' => $marcasCreadas,
  //       'actualizados' => $marcasActualizadas,
  //     ],
  //     'categorias' => [
  //       'creados' => $categoriasCreadas,
  //       'actualizados' => $categoriasActualizadas,
  //     ],
  //     'subcategorias' => [
  //       'creados' => $subcategoriasCreadas,
  //       'actualizados' => $subcategoriasActualizadas,
  //     ],
  //     'unidades' => [
  //       'creados' => $unidadesCreadas,
  //       'actualizados' => $unidadesActualizadas,
  //     ],
  //     'errores' => $errores,
  //     'resumen' => [
  //       'total_intentos' => count($productosData),
  //       'exitosos' => count($productosCreados) + count($productosActualizados),
  //       'creados' => count($productosCreados),
  //       'actualizados' => count($productosActualizados),
  //       'fallidos' => count($errores),
  //     ]
  //   ], 'Proceso de carga masiva finalizado.');
  // }

}
