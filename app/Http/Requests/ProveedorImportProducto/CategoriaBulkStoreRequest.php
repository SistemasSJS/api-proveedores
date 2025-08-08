<?php

namespace App\Http\Requests\ProveedorImportProducto;

use Illuminate\Foundation\Http\FormRequest;

class CategoriaBulkStoreRequest extends FormRequest
{
  public function rules()
  {
    return [
      'categorias' => 'required|array|min:1',
      'categorias.*.nombre' => 'required|string|max:255',
      'categorias.*.descripcion' => 'nullable|string|max:500',
      'categorias.*.subcategoria' => 'nullable|string|max:255',
    ];
  }
}
