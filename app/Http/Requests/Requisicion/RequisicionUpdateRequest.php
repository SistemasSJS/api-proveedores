<?php


namespace App\Http\Requests\Requisicion;

use Illuminate\Foundation\Http\FormRequest;

class RequisicionUpdateRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'fecha_requerida' => 'sometimes|required|date|after:today',
      'observaciones' => 'nullable|string|max:500',
    ];
  }

  public function messages(): array
  {
    return [
      'fecha_requerida.required' => 'La fecha requerida es obligatoria.',
      'fecha_requerida.after' => 'La fecha requerida debe ser posterior a hoy.',
      'observaciones.max' => 'Las observaciones no pueden exceder 500 caracteres.',
    ];
  }
}
