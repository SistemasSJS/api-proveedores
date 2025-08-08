<?php

namespace App\Http\Requests\ProveedorImportProducto;

use Illuminate\Foundation\Http\FormRequest;

class ProductoBulkStoreJsonRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'file' => ['required', 'file'], // Máx 10MB
    ];
  }
}
