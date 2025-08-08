<?php

namespace App\Http\Requests\ProveedorImportProducto;

use Illuminate\Foundation\Http\FormRequest;

class MarcaBulkStoreRequest extends FormRequest
{
  public function rules()
  {
    return [
      'marcas' => 'required|array|min:1',
      'marcas.*.nombre' => 'required|string|max:255',
      'marcas.*.descripcion' => 'nullable|string|max:500',
    ];
  }
}
