<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Validation\Rule;
use App\Enums\EstadoUsuario;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ProveedorUpdateRequest",
 *     required={
 *         "nombre_propietario",
 *         "nombre_de_quien_registra",
 *         "nombre_comercial",
 *         "razon_social",
 *         "tipos_empresa_id",
 *         "descripcion_giro_empresa",
 *         "direccion_empresa",
 *         "email",
 *         "telefono",
 *         "pagina_web",
 *         "estado",
 *         "municipio",
 *         "codigo_postal",
 *         "contacto_nombre",
 *         "contacto_cargo",
 *         "contacto_telefono",
 *         "contacto_correo"
 *     }
 * )
 */
class ProveedorUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pagina_web' => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['sometimes', 'string', 'max:255'],
            'telefono' => ['sometimes', 'required', 'string', 'max:15'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],

            'direccion_empresa' => ['nullable', 'string', 'max:255'],
            'descripcion_giro_empresa' => ['nullable', 'string', 'max:255'],
            'nombre_propietario' => ['nullable', 'string', 'max:255'],
            'nombre_de_quien_registra' => ['nullable', 'string', 'max:255'],

            // -------- DATOS FISCALES --------
            'razon_social' => [
                'sometimes',
                'string',
                'min:3',
                'max:255',
                Rule::unique('proveedores', 'razon_social')->ignore($this->route('proveedor')),
            ],

            'rfc' => [
                'sometimes',
                'string',
                'regex:/^[A-ZÑ&]{3,4}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[A-Z0-9]{3}$/',
                Rule::unique('proveedores', 'rfc')->ignore($this->route('proveedor')),
            ],

            'regimen_fiscal_clave' => ['sometimes', 'string', 'max:10'],
            'regimen_fiscal_nombre' => ['sometimes', 'string', 'max:255'],
            'calle' => ['sometimes', 'string', 'max:255'],
            'numero_exterior' => ['nullable', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['sometimes', 'string', 'max:255'],
            'estado' => ['sometimes', 'string', 'max:255'],
            'ciudad' => ['sometimes', 'string', 'max:255'],
            'codigo_postal' => ['sometimes', 'string', 'regex:/^[0-9]{5}$/'],
            'pais' => ['sometimes', 'string', 'max:255'],

            // -------- CONTACTO --------
            'contacto_nombre' => ['nullable', 'string', 'max:150'],
            'contacto_cargo' => ['nullable', 'string', 'max:60'],
            'contacto_telefono' => ['nullable', 'string', 'max:15'],
            'contacto_correo' => ['nullable', 'email', 'max:60'],
        ];
    }

    public function messages()
    {
        return [
            // Logo
            'logo.image' => 'El archivo debe ser una imagen válida.',
            'logo.mimes' => 'La imagen debe estar en formato JPG o PNG.',
            'logo.max' => 'La imagen no debe pesar más de 2MB.',

            // Generales
            'nombre_propietario.string' => 'El nombre del propietario debe ser una cadena de texto.',
            'nombre_propietario.max' => 'El nombre del propietario no debe exceder los 255 caracteres.',

            'nombre_de_quien_registra.string' => 'El nombre de quien registra debe ser una cadena de texto.',
            'nombre_de_quien_registra.max' => 'El nombre de quien registra no debe exceder los 255 caracteres.',

            'nombre_comercial.string' => 'El nombre comercial debe ser una cadena de texto.',
            'nombre_comercial.max' => 'El nombre comercial no debe exceder los 255 caracteres.',

            // ------- FISCALES -------
            'razon_social.string' => 'La razón social debe ser una cadena de texto.',
            'razon_social.max' => 'La razón social no debe exceder los 255 caracteres.',
            'razon_social.unique' => 'La razón social ingresada ya está registrada.',

            'rfc.string' => 'El RFC debe ser una cadena de texto.',
            'rfc.regex' => 'El RFC no tiene un formato válido según el SAT.',
            'rfc.unique' => 'El RFC ingresado ya está registrado.',

            'regimen_fiscal_clave.string' => 'La clave del régimen fiscal debe ser una cadena de texto.',
            'regimen_fiscal_clave.max' => 'La clave del régimen fiscal no debe exceder los 10 caracteres.',

            'regimen_fiscal_nombre.string' => 'El nombre del régimen fiscal debe ser una cadena de texto.',
            'regimen_fiscal_nombre.max' => 'El nombre del régimen fiscal no debe exceder los 255 caracteres.',

            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El teléfono no debe exceder los 15 caracteres.',

            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está registrado.',

            'codigo_postal.regex' => 'El código postal debe contener exactamente 5 dígitos.',

            // ------- CONTACTO -------
            'contacto_nombre.string' => 'El nombre del contacto debe ser una cadena de texto.',
            'contacto_nombre.max' => 'El nombre del contacto no debe exceder los 150 caracteres.',

            'contacto_cargo.string' => 'El cargo del contacto debe ser una cadena de texto.',
            'contacto_cargo.max' => 'El cargo del contacto no debe exceder los 60 caracteres.',

            'contacto_telefono.string' => 'El teléfono del contacto debe ser una cadena de texto.',
            'contacto_telefono.max' => 'El teléfono del contacto no debe exceder los 15 caracteres.',

            'contacto_correo.email' => 'El correo electrónico del contacto debe tener un formato válido.',
            'contacto_correo.max' => 'El correo electrónico del contacto no debe exceder los 60 caracteres.',

            // Otros
            'estatus.enum' => 'El estatus debe ser uno de los valores permitidos: ' . implode(', ', EstadoUsuario::values()),
        ];
    }
}
