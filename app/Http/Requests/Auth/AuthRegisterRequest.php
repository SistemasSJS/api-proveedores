<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="AuthRegisterRequest",
 *     required={"email", "nombre", "tipo_empresa_id"},
 *     @OA\Property(property="email", type="string", format="email", example="ejemplo@correo.com"),
 *     @OA\Property(property="nombre", type="string", maxLength=255, example="Juan Pérez"),
 *     @OA\Property(property="tipo_empresa_id", type="integer", example=2),
 *     @OA\Property(property="razon_social", type="string", maxLength=255, example="Construcciones Pérez S.A. de C.V."),
 *     @OA\Property(property="nombre_comercial", type="string", maxLength=255, example="Grupo Pérez")
 * )
 */
class AuthRegisterRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'nombre_empresa' => 'required|string|max:255',
      'razons' => 'string|max:255',
      'email' => 'required|email',
      'tipo' => 'required',
      'nombre_comercial' => 'string|max:255',
    ];
  }

  public function messages(): array
  {
    return [

      'nombre_empresa.required' => 'El nombre es obligatorio.',
      'nombre_empresa.string' => 'El nombre debe ser una cadena de texto.',

      'razons.string' => 'La razón social debe ser una cadena de texto.',
      'razons.max' => 'La razón social debe ser una cadena de texto de maximo 255 caracteres.',

      'email.required' => 'El correo electrónico es obligatorio.',
      'email.email' => 'El formato del correo electrónico no es válido.',
      'email.unique' => 'Este correo ya está registrado.',

      'tipo.required' => 'El tipo de empresa es obligatorio.',

      'nombre_comercial.string' => 'El nombre comercial debe ser una cadena de texto.',
      'nombre_comercial.max' => 'El nombre comercial  chdebe ser una cadena de texto de maximo 255 caracteres.',
    ];
  }
}
