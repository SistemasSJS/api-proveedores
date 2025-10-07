<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudPagoConfirmarPagoRequest extends FormRequest
{
  public function authorize(): bool
  {
    // Solo usuarios con rol DA pueden confirmar pagos
    return true; // Cambiar si manejas políticas de autorización
  }

  public function rules(): array
  {
    return [
      'rol' => ['required', 'string', Rule::in(['DA'])],
      'monto_pagado' => ['required', 'numeric', 'min:0.01'],
      'comprobante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
      'observaciones' => ['nullable', 'string', 'max:500'],
    ];
  }

  public function messages(): array
  {
    return [
      'rol.required' => 'Debe especificar el rol que realiza la confirmación.',
      'rol.in' => 'Solo el rol DA (Dirección Administrativa) puede confirmar pagos.',

      'monto_pagado.required' => 'Debe ingresar el monto pagado.',
      'monto_pagado.numeric' => 'El monto pagado debe ser un número válido.',
      'monto_pagado.min' => 'El monto pagado debe ser mayor a cero.',

      'comprobante.required' => 'Debe adjuntar un comprobante de pago.',
      'comprobante.file' => 'El comprobante debe ser un archivo válido.',
      'comprobante.mimes' => 'El comprobante debe ser un archivo PDF o imagen (JPG, JPEG, PNG).',
      'comprobante.max' => 'El comprobante no debe superar los 5 MB.',

      'observaciones.max' => 'Las observaciones no deben exceder los 500 caracteres.',
    ];
  }

  public function attributes(): array
  {
    return [
      'rol' => 'rol del usuario',
      'monto_pagado' => 'monto pagado',
      'comprobante' => 'archivo comprobante',
      'observaciones' => 'comentarios u observaciones',
    ];
  }
}
