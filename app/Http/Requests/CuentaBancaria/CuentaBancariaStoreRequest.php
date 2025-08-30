<?php

namespace App\Http\Requests\CuentaBancaria  ;

use Illuminate\Foundation\Http\FormRequest;

class CuentaBancariaStoreRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // O agregar lógica de permisos
  }

  public function rules(): array
  {
    $tipo = $this->input('tipo_cuenta', 'clabe');

    $rulesCampo = match ($tipo) {
      'clabe' => ['required', 'string', 'size:18'],
      'tarjeta' => ['required', 'string', 'size:16'],
      'cuenta' => ['required', 'string', 'min:10', 'max:10'],
      default => ['required', 'string'],
    };

    return [
      'alias' => ['required', 'string', 'min:3', 'max:50'],
      'titular_cuenta' => ['required', 'string', 'min:2', 'max:100'],
      'banco_clave' => ['required', 'string', 'min:3', 'max:10'],
      'banco_nombre' => ['required', 'string', 'min:3', 'max:50'],
      'tipo_cuenta' => ['required', 'in:clabe,tarjeta,cuenta'],
      'campo_dependiente' => $rulesCampo,
      'referencia' => ['nullable', 'string', 'max:50'],
    ];
  }

  public function messages(): array
  {
    return [
      'alias.required' => 'El alias de la cuenta es obligatorio.',
      'alias.min' => 'El alias debe tener al menos :min caracteres.',
      'alias.max' => 'El alias no puede exceder :max caracteres.',

      'titular_cuenta.required' => 'El nombre del titular es obligatorio.',
      'titular_cuenta.min' => 'El nombre del titular debe tener al menos :min caracteres.',
      'titular_cuenta.max' => 'El nombre del titular no puede exceder :max caracteres.',

      'banco_clave.required' => 'La clave del banco es obligatoria.',
      'banco_clave.min' => 'La clave del banco debe tener al menos :min caracteres.',
      'banco_clave.max' => 'La clave del banco no puede exceder :max caracteres.',

      'banco_nombre.required' => 'El nombre del banco es obligatorio.',
      'banco_nombre.min' => 'El nombre del banco debe tener al menos :min caracteres.',
      'banco_nombre.max' => 'El nombre del banco no puede exceder :max caracteres.',

      'tipo_cuenta.required' => 'El tipo de cuenta es obligatorio.',
      'tipo_cuenta.in' => 'El tipo de cuenta debe ser CLABE, tarjeta o cuenta.',

      'campo_dependiente.required' => 'El campo de la cuenta es obligatorio.',
      'campo_dependiente.size' => 'El campo debe tener exactamente :size caracteres.',
      'campo_dependiente.min' => 'El campo debe tener al menos :min caracteres.',
      'campo_dependiente.max' => 'El campo no puede exceder :max caracteres.',

      'referencia.max' => 'La referencia no puede exceder :max caracteres.',
    ];
  }
}
