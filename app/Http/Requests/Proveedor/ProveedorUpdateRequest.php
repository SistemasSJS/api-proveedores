<?php

namespace App\Http\Requests\Proveedor;

use App\Enums\EstadoUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
 *     },
 *     @OA\Property(property="nombre_propietario", type="string", maxLength=255),
 *     @OA\Property(property="nombre_de_quien_registra", type="string", maxLength=255),
 *     @OA\Property(property="nombre_comercial", type="string", maxLength=255),
 *     @OA\Property(property="razon_social", type="string", maxLength=255),
 *     @OA\Property(property="tipos_empresa_id", type="integer", example=1),
 *     @OA\Property(property="tipos_empresa_otro", type="string", maxLength=60, nullable=true),
 *     @OA\Property(property="descripcion_giro_empresa", type="string", maxLength=255),
 *     @OA\Property(property="direccion_empresa", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255),
 *     @OA\Property(property="telefono", type="string", maxLength=15),
 *     @OA\Property(property="pagina_web", type="string", maxLength=255),
 *     @OA\Property(property="estado", type="string", maxLength=255),
 *     @OA\Property(property="municipio", type="string", maxLength=255),
 *     @OA\Property(property="codigo_postal", type="string", maxLength=10),
 *     @OA\Property(property="direccion_fiscal", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="contacto_nombre", type="string", maxLength=150),
 *     @OA\Property(property="contacto_cargo", type="string", maxLength=60),
 *     @OA\Property(property="contacto_telefono", type="string", maxLength=15),
 *     @OA\Property(property="contacto_correo", type="string", format="email", maxLength=60)
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
            'telefono' => ['nullable', 'string', 'max:15'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion_empresa' => ['nullable', 'string', 'max:255'],
            'descripcion_giro_empresa' => ['nullable', 'string', 'max:255'],

            'nombre_propietario' => ['nullable', 'string', 'max:255'],
            'nombre_de_quien_registra' => ['nullable', 'string', 'max:255'],

            // Datos fiscales
            'razon_social' => ['sometimes', 'string', 'min:3', 'max:255'],
            'rfc' => ['sometimes', 'string', 'regex:/^[A-ZÑ&]{3,4}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[A-Z0-9]{2}[0-9A]$/'],
            'regimen_fiscal_clave' => ['sometimes', 'string', 'max:10'],
            'regimen_fiscal_nombre' => ['sometimes', 'string', 'max:255'],
            'calle' => ['sometimes', 'string', 'max:255'],
            'numero_exterior' => ['sometimes', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['sometimes', 'string', 'max:255'],
            'estado' => ['sometimes', 'string', 'max:255'],
            'ciudad' => ['sometimes', 'string', 'max:255'],
            'codigo_postal' => ['sometimes', 'string', 'regex:/^[0-9]{5}$/'],
            'pais' => ['sometimes', 'string', 'max:255'],

            // Contacto
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

            // Fiscales
            'razon_social.string' => 'La razón social debe ser una cadena de texto.',
            'razon_social.max' => 'La razón social no debe exceder los 255 caracteres.',

            'rfc.string' => 'El RFC debe ser una cadena de texto.',
            'rfc.max' => 'El RFC no debe exceder los 13 caracteres.',

            'regimen_fiscal_clave.string' => 'La clave del régimen fiscal debe ser una cadena de texto.',
            'regimen_fiscal_clave.max' => 'La clave del régimen fiscal no debe exceder los 10 caracteres.',

            'regimen_fiscal_nombre.string' => 'El nombre del régimen fiscal debe ser una cadena de texto.',
            'regimen_fiscal_nombre.max' => 'El nombre del régimen fiscal no debe exceder los 255 caracteres.',

            'tipo_persona.in' => 'El tipo de persona debe ser Física o Moral.',

            'tipos_empresa_id.integer' => 'El tipo de empresa debe ser un número entero.',
            'tipos_empresa_id.exists' => 'El tipo de empresa seleccionado no es válido o está inactivo.',

            'tipos_empresa_otro.string' => 'El campo "otro" del tipo de empresa debe ser una cadena de texto.',
            'tipos_empresa_otro.max' => 'El campo "otro" del tipo de empresa no debe exceder los 60 caracteres.',

            'descripcion_giro_empresa.string' => 'La descripción del giro de la empresa debe ser una cadena de texto.',
            'descripcion_giro_empresa.max' => 'La descripción del giro de la empresa no debe exceder los 255 caracteres.',

            'direccion_empresa.string' => 'La dirección de la empresa debe ser una cadena de texto.',
            'direccion_empresa.max' => 'La dirección de la empresa no debe exceder los 255 caracteres.',

            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está registrado.',

            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El teléfono no debe exceder los 15 caracteres.',

            'pagina_web.string' => 'La página web debe ser una cadena de texto.',
            'pagina_web.max' => 'La página web no debe exceder los 255 caracteres.',

            'estado.string' => 'El estado debe ser una cadena de texto.',
            'estado.max' => 'El estado no debe exceder los 255 caracteres.',

            'municipio.string' => 'El municipio debe ser una cadena de texto.',
            'municipio.max' => 'El municipio no debe exceder los 255 caracteres.',

            'codigo_postal.string' => 'El código postal debe ser una cadena de texto.',
            'codigo_postal.max' => 'El código postal no debe exceder los 10 caracteres.',

            'calle.required' => 'La calle es obligatoria.',
            'calle.string' => 'La calle debe ser una cadena de texto.',
            'calle.max' => 'La calle no debe exceder los 255 caracteres.',

            'numero_exterior.required' => 'El número exterior es obligatorio.',
            'numero_exterior.string' => 'El número exterior debe ser una cadena de texto.',
            'numero_exterior.max' => 'El número exterior no debe exceder los 20 caracteres.',

            'numero_interior.string' => 'El número interior debe ser una cadena de texto.',
            'numero_interior.max' => 'El número interior no debe exceder los 20 caracteres.',

            'colonia.required' => 'La colonia es obligatoria.',
            'colonia.string' => 'La colonia debe ser una cadena de texto.',
            'colonia.max' => 'La colonia no debe exceder los 255 caracteres.',

            'ciudad.required' => 'La ciudad es obligatoria.',
            'ciudad.string' => 'La ciudad debe ser una cadena de texto.',
            'ciudad.max' => 'La ciudad no debe exceder los 255 caracteres.',

            'estado.required' => 'El estado es obligatorio.',
            'estado.string' => 'El estado debe ser una cadena de texto.',
            'estado.max' => 'El estado no debe exceder los 255 caracteres.',

            'codigo_postal.required' => 'El código postal es obligatorio.',
            'codigo_postal.string' => 'El código postal debe ser una cadena de texto.',
            'codigo_postal.regex' => 'El código postal debe contener exactamente 5 dígitos.',

            'pais.required' => 'El país es obligatorio.',
            'pais.string' => 'El país debe ser una cadena de texto.',
            'pais.max' => 'El país no debe exceder los 255 caracteres.',

            // Contacto
            'contacto_nombre.string' => 'El nombre del contacto debe ser una cadena de texto.',
            'contacto_nombre.max' => 'El nombre del contacto no debe exceder los 150 caracteres.',

            'contacto_cargo.string' => 'El cargo del contacto debe ser una cadena de texto.',
            'contacto_cargo.max' => 'El cargo del contacto no debe exceder los 60 caracteres.',

            'contacto_telefono.string' => 'El teléfono del contacto debe ser una cadena de texto.',
            'contacto_telefono.max' => 'El teléfono del contacto no debe exceder los 15 caracteres.',

            'contacto_correo.email' => 'El correo electrónico del contacto debe tener un formato válido.',
            'contacto_correo.max' => 'El correo electrónico del contacto no debe exceder los 60 caracteres.',

            // Estatus
            'estatus' => 'El estatus seleccionado no es válido.',
            'estatus.enum' => 'El estatus debe ser uno de los valores permitidos: ' . implode(', ', EstadoUsuario::values()),
        ];
    }
}
