<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AuthUpdateCredentialsRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // Ajusta según tus políticas de autorización
  }

  public function rules(): array
  {
    return [
      'nombre' => ['nullable', 'string', 'max:255'],
      'current_password' => ['required_with:new_password', 'string', 'min:6'],
      'new_password' => ['nullable', 'string', 'min:6', 'confirmed'],
      // 'confirmed' automáticamente valida que exista 'new_password_confirmation'
    ];
  }

  public function messages(): array
  {
    return [
      'nombre.string' => 'El nombre debe ser una cadena de texto.',
      'nombre.max' => 'El nombre no puede exceder 255 caracteres.',

      'current_password.required_with' => 'La contraseña actual es requerida para cambiar la contraseña.',
      'current_password.string' => 'La contraseña actual debe ser una cadena de texto.',
      'current_password.min' => 'La contraseña actual debe tener al menos 6 caracteres.',

      'new_password.string' => 'La nueva contraseña debe ser una cadena de texto.',
      'new_password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
      'new_password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
    ];
  }
}
