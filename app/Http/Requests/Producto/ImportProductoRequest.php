<?php

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductoRequest extends FormRequest
{
  public function authorize()
  {
    return true;
  }

  public function rules()
  {
    return [

      'file' => 'required|file|mimes:csv,txt'
    ];
  }

  public function messages()
  {
    return [
      'file.required' => 'El file es obligatorio.',
      'file.file' => 'El file...',
      'file.mimes' => 'El file debe ser: CSV TXT.',
    ];
  }
}
