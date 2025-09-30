<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorRegistroBasicoRequest extends FormRequest
{
  public function authorize(): bool
  {
    // Permitir que cualquier usuario pueda enviar este request
    return true;
  }

  public function rules(): array
  {
    return [
      'empresa' => ['required', 'string', 'max:255'],
      'alias' => ['nullable', 'string', 'max:255'],
      'rfc' => [
        'required',
        'string',
        'min:12',
        'max:13',
        'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/'
      ],
      'razon_social' => ['required', 'string', 'max:255'],
      // Si más adelante agregas email:
      // 'email' => ['nullable', 'email', 'max:255', Rule::unique('proveedores', 'email')],
    ];
  }

  public function messages(): array
  {
    return [
      'empresa.required' => 'El nombre de la empresa es obligatorio.',
      'empresa.string' => 'El nombre de la empresa debe ser una cadena de texto.',
      'empresa.max' => 'El nombre de la empresa no debe exceder los 255 caracteres.',

      'alias.string' => 'El alias de la empresa debe ser una cadena de texto.',
      'alias.max' => 'El alias de la empresa no debe exceder los 255 caracteres.',

      'rfc.required' => 'El RFC es obligatorio.',
      'rfc.string' => 'El RFC debe ser una cadena de texto.',
      'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
      'rfc.max' => 'El RFC no debe exceder los 13 caracteres.',
      'rfc.regex' => 'El RFC no tiene un formato válido.',

      'razon_social.required' => 'La razón social es obligatoria.',
      'razon_social.string' => 'La razón social debe ser una cadena de texto.',
      'razon_social.max' => 'La razón social no debe exceder los 255 caracteres.',
    ];
  }
}
