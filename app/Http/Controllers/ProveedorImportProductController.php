<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use App\Models\Categoria;
use App\Models\UnidadMedida;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Http\Requests\ProveedorImportProducto\MarcaBulkStoreRequest;
use App\Http\Requests\ProveedorImportProducto\CategoriaBulkStoreRequest;
use App\Http\Requests\ProveedorImportProducto\UnidadMedidaBulkStoreRequest;
use App\Http\Resources\ElementoImportadoResource;

class ProveedorImportProductController extends Controller
{
  use ApiResponse;

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
}
