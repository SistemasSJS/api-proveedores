<?php


namespace App\Services;

use App\Models\Producto;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductoSearchService
{
  /**
   * Búsqueda avanzada de productos con filtros
   */
  public function buscar(array $filtros): LengthAwarePaginator
  {
    $query = Producto::with(['proveedor', 'marca', 'categoria'])
      ->where('activo', true);

    // Búsqueda por texto
    if (!empty($filtros['buscar'])) {
      $buscar = $filtros['buscar'];
      $query->where(function ($q) use ($buscar) {
        $q->where('nombre', 'like', "%{$buscar}%")
          ->orWhere('descripcion', 'like', "%{$buscar}%")
          ->orWhere('sku', 'like', "%{$buscar}%");
      });
    }

    // Filtros específicos
    if (!empty($filtros['proveedor_id'])) {
      $query->where('proveedor_id', $filtros['proveedor_id']);
    }

    if (!empty($filtros['categoria_id'])) {
      $query->where('categoria_id', $filtros['categoria_id']);
    }

    if (!empty($filtros['marca_id'])) {
      $query->where('marca_id', $filtros['marca_id']);
    }

    // Rango de precios
    if (!empty($filtros['precio_min'])) {
      $query->where('precio_base', '>=', $filtros['precio_min']);
    }

    if (!empty($filtros['precio_max'])) {
      $query->where('precio_base', '<=', $filtros['precio_max']);
    }

    // // Solo productos con stock
    // if (!empty($filtros['con_stock'])) {
    //   $query->where('stock', '>', 0);
    // }

    // Ordenamiento
    $ordenPor = $filtros['orden_por'] ?? 'nombre';
    $direccion = $filtros['direccion'] ?? 'asc';

    $query->orderBy($ordenPor, $direccion);

    return $query->paginate($filtros['per_page'] ?? 20);
  }

  /**
   * Búsqueda específica para crear requisiciones
   */
  public function buscarParaRequisicion(int $proveedorId, string $termino): array
  {
    $productos = Producto::where('proveedor_id', $proveedorId)
      ->where('activo', true)
      ->where('stock', '>', 0)
      ->where(function ($query) use ($termino) {
        $query->where('nombre', 'like', "%{$termino}%")
          ->orWhere('sku', 'like', "%{$termino}%")
          ->orWhere('descripcion', 'like', "%{$termino}%");
      })
      ->with(['marca', 'categoria'])
      ->limit(20)
      ->get();

    return $productos->map(function ($producto) {
      return [
        'id' => $producto->id,
        'sku' => $producto->sku,
        'nombre' => $producto->nombre,
        'descripcion' => $producto->descripcion,
        'precio_base' => $producto->precio_base,
        'stock' => $producto->stock,
        'marca' => $producto->marca?->nombre,
        'categoria' => $producto->categoria?->nombre,
        'imagen_principal' => $producto->imagen_principal,
      ];
    })->toArray();
  }
}
