<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class ConstruccProveedorGenerarSppRequest extends FormRequest
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

            // Cuenta bancaria del proveedor (debe existir y pertenecer al proveedor)
            'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',

            // Recursos de construcción
            'empresa_construcc_id' => 'required|exists:empresa_construcc,id',
            'usuario_id' => 'required|integer',
            'usuario_nombre' => 'required|string|max:255',
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

            // Mensajes para cuenta bancaria
            'cuenta_bancaria_id.required' => 'La cuenta bancaria es obligatoria',
            'cuenta_bancaria_id.exists' => 'La cuenta bancaria seleccionada no existe',

            // Mensajes para recursos de construcción
            'empresa_construcc_id.required' => 'La empresa de construcción es obligatoria',
            'empresa_construcc_id.exists' => 'La empresa de construcción seleccionada no existe',
            'usuario_id.required' => 'El ID del usuario es obligatorio',
            'usuario_id.integer' => 'El ID del usuario debe ser un número entero',
            'usuario_nombre.required' => 'El nombre del usuario es obligatorio',
            'usuario_nombre.max' => 'El nombre del usuario no debe exceder 255 caracteres',
            'nivel_id.integer' => 'El nivel del usuario debe ser un número entero',
            'nivel_id.min' => 'El nivel del usuario debe ser mayor o igual a 0',
            'nivel_id.max' => 'El nivel del usuario no debe exceder 6',
        ];
    }
}
