<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuthUpdateUserDataRequest extends FormRequest
{
  public function authorize(): bool
  {
    return auth()->check(); // más seguro que devolver true siempre
  }

  public function rules(): array
  {
    $userId = auth()->id();

    return [
      'name' => ['sometimes', 'string', 'max:255'],

      'email' => [
        'sometimes',
        'email',
        Rule::unique('users', 'email')->ignore($userId),
      ],

      'telefono' => [
        'sometimes',
        'string',
        'max:20',
        Rule::unique('users', 'telefono')->ignore($userId),
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'name.string' => 'El nombre debe ser una cadena de texto.',
      'name.max' => 'El nombre debe tener máximo 255 caracteres.',

      'email.email' => 'El formato del correo electrónico no es válido.',
      'email.unique' => 'Este correo ya está registrado.',

      'telefono.string' => 'El teléfono debe ser una cadena de texto.',
      'telefono.max' => 'El teléfono debe tener máximo 20 caracteres.',
      'telefono.unique' => 'Este teléfono ya está registrado.',
    ];
  }
}
