<?php

namespace App\Http\Requests\Proveedor;

use App\Rules\ReCaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="ProveedorRegisterRequest",
 *     required={
 *         "nombre_comercial",
 *         "razon_social",
 *         "tipos_empresa_id",
 *         "email",
 *         "telefono",
 *         "contacto_nombre",
 *         "contacto_telefono",
 *         "contacto_correo",
 *         "recaptcha_token"
 *     },
 *     @OA\Property(property="nombre_comercial", type="string", maxLength=255),
 *     @OA\Property(property="razon_social", type="string", maxLength=255),
 *     @OA\Property(property="tipos_empresa_id", type="integer", example=1),
 *     @OA\Property(property="tipos_empresa_otro", type="string", maxLength=60, nullable=true),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255),
 *     @OA\Property(property="telefono", type="string", maxLength=15),
 *     @OA\Property(property="contacto_nombre", type="string", maxLength=150),
 *     @OA\Property(property="contacto_telefono", type="string", maxLength=15),
 *     @OA\Property(property="contacto_correo", type="string", format="email", maxLength=60),
 *     @OA\Property(property="recaptcha_token", type="string", description="Token de validación ReCAPTCHA v3")
 * )
 */
class ProveedorRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_comercial' => ['required', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'tipos_empresa_id' => ['required', 'integer', 'exists:tipos_empresa,id,estatus,activo'],
            'tipos_empresa_otro' => ['string', 'max:60'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email'),
                Rule::unique('proveedores', 'email'),
            ],
            'telefono' => ['required', 'string', 'max:15'],
            'contacto_nombre' => ['required', 'string', 'max:150'],
            'contacto_telefono' => ['required', 'string', 'max:15'],
            'contacto_correo' => ['required', 'email', 'max:60'],
            'recaptcha_token' => ['required', new ReCaptcha],

        ];
    }

    public function messages()
    {
        return [

            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'nombre_comercial.string' => 'El nombre comercial debe ser una cadena de texto.',
            'nombre_comercial.max' => 'El nombre comercial no debe exceder los 255 caracteres.',

            'razon_social.required' => 'La razón social es obligatoria.',
            'razon_social.string' => 'La razón social debe ser una cadena de texto.',
            'razon_social.max' => 'La razón social no debe exceder los 255 caracteres.',


            'tipos_empresa_id.required' => 'El tipo de empresa es obligatorio.',
            'tipos_empresa_id.integer' => 'El tipo de empresa debe ser un número entero.',
            'tipos_empresa_id.exists' => 'El tipo de empresa seleccionado no es válido o está inactivo.',

            'tipos_empresa_otro.string' => 'El campo "otro" del tipo de empresa debe ser una cadena de texto.',
            'tipos_empresa_otro.max' => 'El campo "otro" del tipo de empresa no debe exceder los 60 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está registrado.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El teléfono no debe exceder los 15 caracteres.',

            'contacto_nombre.required' => 'El nombre del contacto es obligatorio.',
            'contacto_nombre.string' => 'El nombre del contacto debe ser una cadena de texto.',
            'contacto_nombre.max' => 'El nombre del contacto no debe exceder los 150 caracteres.',

            'contacto_telefono.required' => 'El teléfono del contacto es obligatorio.',
            'contacto_telefono.string' => 'El teléfono del contacto debe ser una cadena de texto.',
            'contacto_telefono.max' => 'El teléfono del contacto no debe exceder los 15 caracteres.',

            'contacto_correo.required' => 'El correo electrónico del contacto es obligatorio.',
            'contacto_correo.email' => 'El correo electrónico del contacto debe tener un formato válido.',
            'contacto_correo.max' => 'El correo electrónico del contacto no debe exceder los 60 caracteres.',
        ];
    }
}
