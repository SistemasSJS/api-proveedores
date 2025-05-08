<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_propietario' => ['required', 'string', 'max:255'],
            'nombre_de_quien_registra' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['required', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'tipos_empresa_id' => ['required', 'integer', 'exists:tipos_empresa,id,estatus,activo'],
            'tipos_empresa_otro' => ['string', 'max:60'],
            'descripcion_giro_empresa' => ['required', 'string', 'max:255'],
            'direccion_empresa' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:proveedores'],
            'telefono' => ['required', 'string', 'max:15'],
            'pagina_web' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', 'max:255'],
            'municipio' => ['required', 'string', 'max:255'],
            'codigo_postal' => ['required', 'string', 'max:10'],
            'direccion_fiscal' => ['nullable', 'string', 'max:255'],
            'contacto_nombre' => ['required', 'string', 'max:150'],
            'contacto_cargo' => ['required', 'string', 'max:60'],
            'contacto_telefono' => ['required', 'string', 'max:15'],
            'contacto_correo' => ['required', 'email', 'max:60'],
        ];
    }
    public function messages()
    {
        return [
            'nombre_propietario.required' => 'El nombre del propietario es obligatorio.',
            'nombre_propietario.string' => 'El nombre del propietario debe ser una cadena de texto.',
            'nombre_propietario.max' => 'El nombre del propietario no debe exceder los 255 caracteres.',

            'nombre_de_quien_registra.required' => 'El nombre de quien registra es obligatorio.',
            'nombre_de_quien_registra.string' => 'El nombre de quien registra debe ser una cadena de texto.',
            'nombre_de_quien_registra.max' => 'El nombre de quien registra no debe exceder los 255 caracteres.',

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

            'descripcion_giro_empresa.required' => 'La descripción del giro de la empresa es obligatoria.',
            'descripcion_giro_empresa.string' => 'La descripción del giro de la empresa debe ser una cadena de texto.',
            'descripcion_giro_empresa.max' => 'La descripción del giro de la empresa no debe exceder los 255 caracteres.',

            'direccion_empresa.required' => 'La dirección de la empresa es obligatoria.',
            'direccion_empresa.string' => 'La dirección de la empresa debe ser una cadena de texto.',
            'direccion_empresa.max' => 'La dirección de la empresa no debe exceder los 255 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está registrado.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El teléfono no debe exceder los 15 caracteres.',

            'pagina_web.required' => 'La página web es obligatoria.',
            'pagina_web.string' => 'La página web debe ser una cadena de texto.',
            'pagina_web.max' => 'La página web no debe exceder los 255 caracteres.',

            'estado.required' => 'El estado es obligatorio.',
            'estado.string' => 'El estado debe ser una cadena de texto.',
            'estado.max' => 'El estado no debe exceder los 255 caracteres.',

            'municipio.required' => 'El municipio es obligatorio.',
            'municipio.string' => 'El municipio debe ser una cadena de texto.',
            'municipio.max' => 'El municipio no debe exceder los 255 caracteres.',

            'codigo_postal.required' => 'El código postal es obligatorio.',
            'codigo_postal.string' => 'El código postal debe ser una cadena de texto.',
            'codigo_postal.max' => 'El código postal no debe exceder los 10 caracteres.',

            'direccion_fiscal.string' => 'La dirección fiscal debe ser una cadena de texto.',
            'direccion_fiscal.max' => 'La dirección fiscal no debe exceder los 255 caracteres.',

            'contacto_nombre.required' => 'El nombre del contacto es obligatorio.',
            'contacto_nombre.string' => 'El nombre del contacto debe ser una cadena de texto.',
            'contacto_nombre.max' => 'El nombre del contacto no debe exceder los 150 caracteres.',

            'contacto_cargo.required' => 'El cargo del contacto es obligatorio.',
            'contacto_cargo.string' => 'El cargo del contacto debe ser una cadena de texto.',
            'contacto_cargo.max' => 'El cargo del contacto no debe exceder los 60 caracteres.',

            'contacto_telefono.required' => 'El teléfono del contacto es obligatorio.',
            'contacto_telefono.string' => 'El teléfono del contacto debe ser una cadena de texto.',
            'contacto_telefono.max' => 'El teléfono del contacto no debe exceder los 15 caracteres.',

            'contacto_correo.required' => 'El correo electrónico del contacto es obligatorio.',
            'contacto_correo.email' => 'El correo electrónico del contacto debe tener un formato válido.',
            'contacto_correo.max' => 'El correo electrónico del contacto no debe exceder los 60 caracteres.',
        ];
    }
}
