<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Foundation\Http\FormRequest;

class ProveedorUpdateConstanciaFiscalRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // Si tienes policies, puedes ajustarlo
  }

  public function rules(): array
  {
    return [
      'constancia_fiscal' => 'required|file|mimes:pdf|max:5120',
      // solo PDFs hasta 5MB
    ];
  }
}
