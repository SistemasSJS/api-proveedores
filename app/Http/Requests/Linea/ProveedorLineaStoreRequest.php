<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Foundation\Http\FormRequest;

class ProveedorLineaStoreRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // Puedes aplicar lógica de autorización aquí si es necesario
  }

  public function rules(): array
  {
    return [
      'nombre'    => ['required', 'string', 'max:100'],
      'marca_id'  => ['required', 'exists:marcas,id'],
    ];
  }

  public function messages(): array
  {
    return [
      'nombre.required'   => 'El nombre de la línea es obligatorio.',
      'nombre.string'     => 'El nombre debe ser una cadena de texto.',
      'nombre.max'        => 'El nombre no debe exceder los 100 caracteres.',
      'marca_id.required' => 'Debe seleccionar una marca.',
      'marca_id.exists'   => 'La marca seleccionada no es válida.',
    ];
  }
}
