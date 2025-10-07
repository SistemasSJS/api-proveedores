<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudPagoAutorizarRequest extends FormRequest
{
  public function authorize(): bool
  {
    // Todos los roles DG, DT, PC, SI pueden autorizar
    return true;
  }

  public function rules(): array
  {
    return [
      'rol' => ['required', 'string', Rule::in(['DG', 'DT', 'PC', 'SI'])],
    ];
  }

  public function messages(): array
  {
    return [
      'rol.required' => 'Debe indicar el rol que autoriza la solicitud.',
      'rol.in' => 'El rol debe ser uno de los siguientes: DG, DT, PC o SI.',
    ];
  }

  public function attributes(): array
  {
    return [
      'rol' => 'rol que autoriza',
    ];
  }
}
