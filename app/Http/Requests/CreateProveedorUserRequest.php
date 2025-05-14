<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="RegistrarProveedorUserRequest",
 *     required={
 *         "razon_social", "nombre_comercial", "rfc", "email", "password",
 *         "telefono", "direccion"
 *     },
 *     @OA\Property(property="nombre_propietario", type="string", maxLength=255),
 *     @OA\Property(property="nombre_de_quien_registra", type="string", maxLength=255),
 *     @OA\Property(property="nombre_comercial", type="string", maxLength=255),
 *     @OA\Property(property="razon_social", type="string", maxLength=255),
 *     @OA\Property(property="rfc", type="string", maxLength=13),
 *     @OA\Property(property="tipos_empresa_id", type="integer", example=1),
 *     @OA\Property(property="tipos_empresa_otro", type="string", maxLength=60, nullable=true),
 *     @OA\Property(property="descripcion_giro_empresa", type="string", maxLength=255),
 *     @OA\Property(property="direccion_empresa", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255),
 *     @OA\Property(property="password", type="string", format="password", minLength=8),
 *     @OA\Property(property="telefono", type="string", maxLength=15),
 *     @OA\Property(property="pagina_web", type="string", maxLength=255),
 *     @OA\Property(property="estado", type="string", maxLength=255),
 *     @OA\Property(property="municipio", type="string", maxLength=255),
 *     @OA\Property(property="codigo_postal", type="string", maxLength=10),
 *     @OA\Property(property="direccion", type="string", maxLength=255),
 *     @OA\Property(property="direccion_fiscal", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="contacto_nombre", type="string", maxLength=150),
 *     @OA\Property(property="contacto_cargo", type="string", maxLength=60),
 *     @OA\Property(property="contacto_telefono", type="string", maxLength=15),
 *     @OA\Property(property="contacto_correo", type="string", format="email", maxLength=60)
 * )
 */
class RegistrarProveedorUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['required', 'string', 'max:255'],
            'rfc' => ['required', 'string', 'max:13'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'telefono' => ['required', 'string', 'max:15'],
            'direccion' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'razon_social.required' => 'La razón social es obligatoria.',
            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'rfc.required' => 'El RFC es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Esta empresa ya está registrada en el sistema, por favor contacte a soporte técnico.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.max' => 'El teléfono no debe exceder los 15 caracteres.',
            'direccion.required' => 'La dirección es obligatoria.',
            'direccion.max' => 'La dirección no debe exceder los 255 caracteres.',
        ];
    }
}
