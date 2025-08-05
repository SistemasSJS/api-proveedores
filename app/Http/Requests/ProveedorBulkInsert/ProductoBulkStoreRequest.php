<?php

namespace App\Http\Requests\ProductoImport;

use Illuminate\Foundation\Http\FormRequest;

class ProductoBulkStoreRequest extends FormRequest
{
  public function rules()
  {
    return [
      'productos' => 'required|array|min:1',
      'productos.*.codigo' => 'required|string|max:255',
      'productos.*.producto' => 'required|string|max:255',
      'productos.*.descripcion' => 'nullable|string',
      'productos.*.marca' => 'nullable|string|max:255',
      'productos.*.categoria' => 'nullable|string|max:255',
      'productos.*.subcategoria' => 'nullable|string|max:255',
      'productos.*.modelo' => 'nullable|string|max:255',
      'productos.*.unidad_medida' => 'nullable|string|max:255',
      'productos.*.precio' => 'required|numeric|min:0',
      'productos.*.precio_mayoreo' => 'nullable|numeric|min:0',
      'productos.*.precio_menudeo' => 'nullable|numeric|min:0',
    ];
  }
}
