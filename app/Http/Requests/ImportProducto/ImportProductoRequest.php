<?php

namespace App\Http\Requests\ImportProducto;

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

      'file' => 'required|file|mimes:csv,txt,json,xlsx,xls|max:10240'
    ];
  }

  public function messages()
  {
    return [
      'file.required' => 'El file es obligatorio.',
      'file.file' => 'El file...',
      'file.mimes' => 'El archivo debe ser de tipo: CSV, TXT, JSON, XLSX o XLS.',
      'file.max' => 'El archivo no debe exceder los 10MB.',
    ];
  }
}
