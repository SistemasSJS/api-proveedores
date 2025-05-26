<?php

namespace App\Http\Requests;

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
      'email' => 'required|email|unique:proveedores,email',
      'nombre' => 'required|string|max:255',
      'tipo_empresa_id' => 'required|exists:tipos_empresa,id',
      'razon_social' => 'string|max:255',
      'nombre_comercial' => 'string|max:255',
    ];
  }

  public function messages(): array
  {
    return [
      'email.required' => 'El correo electrónico es obligatorio.',
      'email.email' => 'El formato del correo electrónico no es válido.',
      'email.unique' => 'Este correo ya está registrado.',

      'nombre.required' => 'El nombre es obligatorio.',
      'nombre.string' => 'El nombre debe ser una cadena de texto.',

      'tipo_empresa_id.required' => 'El tipo de empresa es obligatorio.',
      'tipo_empresa_id.exists' => 'El tipo de empresa seleccionado no es válido.',

      'razon_social.string' => 'La razón social debe ser una cadena de texto.',

      'nombre_comercial.string' => 'El nombre comercial debe ser una cadena de texto.',
    ];
  }
}
