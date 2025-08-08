<?php

namespace App\Http\Requests\ProveedorImportProducto;

use Illuminate\Foundation\Http\FormRequest;

class UnidadMedidaBulkStoreRequest extends FormRequest
{
  public function rules()
  {
    return [
      'unidades' => 'required|array|min:1',
      'unidades.*.nombre' => 'required|string|max:255',
      'unidades.*.clave' => 'nullable|string|max:255',
      'unidades.*.descripcion' => 'nullable|string|max:500',
    ];
  }
}
