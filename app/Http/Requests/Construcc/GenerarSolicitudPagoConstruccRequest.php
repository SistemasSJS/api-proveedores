<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class GenerarSolicitudPagoConstruccRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Datos generales de la SPP
            'descripcion_concepto' => 'required|string|min:1|max:500',
            'monto_total' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:1000',

            // Archivos
            'factura_pdf' => 'required|file|mimes:pdf|max:10240',
            'factura_xml' => 'required|file|mimes:xml|max:5120',
            'cotizacion' => 'nullable|file|mimes:pdf,jpg,jpeg,png,bmp,gif,webp,doc,docx,xls,xlsx|max:10240',

            // Datos del proveedor a crear/validar
            'proveedor_rfc' => 'required|string|min:12|max:13',
            'proveedor_razon_social' => 'required|string|max:255',
            'proveedor_nombre_comercial' => 'required|string|max:255',
            'proveedor_email' => 'required|email|max:255',
            'proveedor_telefono' => 'required|string|max:20',
            'proveedor_celular' => 'nullable|string|max:20',

            // Datos de cuenta bancaria del proveedor
            'cuenta_bancaria_alias' => 'required|string|max:255',
            'cuenta_bancaria_banco_clave' => 'required|string|max:10',
            'cuenta_bancaria_banco_nombre' => 'required|string|max:255',
            'cuenta_bancaria_tipo_cuenta' => 'required|string|max:255',
            'cuenta_bancaria_campo_dependiente' => 'required|string|max:255',
            'cuenta_bancaria_titular_cuenta' => 'required|string|max:255',
            'cuenta_bancaria_referencia' => 'nullable|string|max:255',
            'cuenta_bancaria_sucursal' => 'nullable|string|max:255',
            'cuenta_bancaria_swift' => 'nullable|string|max:255',

            // Recursos de construcción
            'empresa_construcc_id' => 'nullable|exists:empresa_construcc,id',
            'empresa' => 'nullable|string|max:255',
            'usuario_id' => 'nullable|integer',
            'usuario_nombre' => 'nullable|string|max:255',
            'nivel_id' => 'nullable|integer|min:0|max:6', // 0: Admin, 1: DG, 2: DT, 3: DA, 4: SI, 5: PC, 6: RO
        ];
    }

    public function messages()
    {
        return [
            // Mensajes para datos generales
            'descripcion_concepto.required' => 'La descripción del concepto es obligatoria',
            'descripcion_concepto.min' => 'La descripción debe tener al menos 1 caracter',
            'descripcion_concepto.max' => 'La descripción no debe exceder los 500 caracteres',
            'monto_total.required' => 'El monto total es obligatorio',
            'monto_total.numeric' => 'El monto total debe ser un número válido',
            'monto_total.min' => 'El monto total no puede ser negativo',
            'observaciones.max' => 'Las observaciones no deben exceder los 1000 caracteres',

            // Mensajes para archivos
            'factura_pdf.required' => 'El archivo PDF de la factura es obligatorio',
            'factura_pdf.mimes' => 'El archivo debe ser un PDF válido',
            'factura_pdf.max' => 'El archivo PDF no debe superar los 10MB',
            'factura_xml.required' => 'El archivo XML de la factura es obligatorio',
            'factura_xml.mimes' => 'El archivo debe ser un XML válido',
            'factura_xml.max' => 'El archivo XML no debe superar los 5MB',
            'cotizacion.mimes' => 'El archivo de cotización debe ser un formato válido (PDF, imagen o documento)',
            'cotizacion.max' => 'El archivo de cotización no debe superar los 10MB',

            // Mensajes para datos del proveedor
            'proveedor_rfc.required' => 'El RFC del proveedor es obligatorio',
            'proveedor_rfc.min' => 'El RFC debe tener al menos 12 caracteres',
            'proveedor_rfc.max' => 'El RFC no debe exceder los 13 caracteres',
            'proveedor_razon_social.required' => 'La razón social del proveedor es obligatoria',
            'proveedor_razon_social.max' => 'La razón social no debe exceder los 255 caracteres',
            'proveedor_nombre_comercial.required' => 'El nombre comercial del proveedor es obligatorio',
            'proveedor_nombre_comercial.max' => 'El nombre comercial no debe exceder los 255 caracteres',
            'proveedor_email.required' => 'El email del proveedor es obligatorio',
            'proveedor_email.email' => 'El email del proveedor debe ser una dirección válida',
            'proveedor_email.max' => 'El email no debe exceder los 255 caracteres',
            'proveedor_telefono.required' => 'El teléfono del proveedor es obligatorio',
            'proveedor_telefono.max' => 'El teléfono no debe exceder los 20 caracteres',
            'proveedor_celular.max' => 'El celular no debe exceder los 20 caracteres',

            // Mensajes para cuenta bancaria
            'cuenta_bancaria_alias.required' => 'El alias de la cuenta bancaria es obligatorio',
            'cuenta_bancaria_alias.max' => 'El alias no debe exceder los 255 caracteres',
            'cuenta_bancaria_banco_clave.required' => 'La clave del banco es obligatoria',
            'cuenta_bancaria_banco_clave.max' => 'La clave del banco no debe exceder los 10 caracteres',
            'cuenta_bancaria_banco_nombre.required' => 'El nombre del banco es obligatorio',
            'cuenta_bancaria_banco_nombre.max' => 'El nombre del banco no debe exceder los 255 caracteres',
            'cuenta_bancaria_tipo_cuenta.required' => 'El tipo de cuenta es obligatorio',
            'cuenta_bancaria_tipo_cuenta.max' => 'El tipo de cuenta no debe exceder los 255 caracteres',
            'cuenta_bancaria_campo_dependiente.required' => 'El campo dependiente (CLABE/número de cuenta) es obligatorio',
            'cuenta_bancaria_campo_dependiente.max' => 'El campo dependiente no debe exceder los 255 caracteres',
            'cuenta_bancaria_titular_cuenta.required' => 'El titular de la cuenta es obligatorio',
            'cuenta_bancaria_titular_cuenta.max' => 'El titular de la cuenta no debe exceder los 255 caracteres',
            'cuenta_bancaria_referencia.max' => 'La referencia no debe exceder los 255 caracteres',
            'cuenta_bancaria_sucursal.max' => 'La sucursal no debe exceder los 255 caracteres',
            'cuenta_bancaria_swift.max' => 'El código SWIFT no debe exceder los 255 caracteres',

            // Mensajes para recursos de construcción
            'empresa_construcc_id.exists' => 'La empresa de construcción seleccionada no existe',
            'empresa.max' => 'El nombre de la empresa no debe exceder 255 caracteres',
            'usuario_id.integer' => 'El ID del usuario debe ser un número entero',
            'usuario_nombre.max' => 'El nombre del usuario no debe exceder 255 caracteres',
            'nivel_id.integer' => 'El nivel del usuario debe ser un número entero',
            'nivel_id.min' => 'El nivel del usuario debe ser mayor o igual a 0',
            'nivel_id.max' => 'El nivel del usuario no debe exceder 6',
        ];
    }
}
