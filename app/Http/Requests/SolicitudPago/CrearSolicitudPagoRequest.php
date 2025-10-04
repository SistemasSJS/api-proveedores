<?php

namespace App\Http\Requests\SolicitudPago;

use Illuminate\Foundation\Http\FormRequest;

class CrearSolicitudPagoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'descripcion_concepto'   => 'required|string|min:10|max:500',
            'factura_pdf'            => 'required|file|mimes:pdf|max:10240',
            'factura_xml'            => 'required|file|mimes:xml|max:5120',
            'cotizacion'             => 'nullable|file|mimes:pdf,jpg,jpeg,png,bmp,gif,webp,doc,docx,xls,xlsx|max:10240',
            'proveedor_id'           => 'required|exists:proveedores,id',
            // 'empresa_construcc_id'   => 'nullable|exists:empresa_construcc,id',
            'empresa'                => 'nullable|string|max:255',
            'residente'              => 'nullable|string|max:255',
            'cotizacion_id'          => 'nullable|integer',
            'monto_total'            => 'required|numeric|min:0', // <- agregado
        ];
    }

    public function messages()
    {
        return [
            'descripcion_concepto.required'   => 'La descripción del concepto es obligatoria',
            'descripcion_concepto.min'        => 'La descripción debe tener al menos 10 caracteres',
            'descripcion_concepto.max'        => 'La descripción no debe exceder los 500 caracteres',
            'factura_pdf.required'            => 'El archivo PDF de la factura es obligatorio',
            'factura_pdf.mimes'               => 'El archivo debe ser un PDF válido',
            'factura_pdf.max'                 => 'El archivo PDF no debe superar los 10MB',
            'factura_xml.required'            => 'El archivo XML de la factura es obligatorio',
            'factura_xml.mimes'               => 'El archivo debe ser un XML válido',
            'factura_xml.max'                 => 'El archivo XML no debe superar los 5MB',
            'cotizacion.mimes'                => 'El archivo de cotización debe ser un formato válido (PDF, imagen o documento)',
            'cotizacion.max'                  => 'El archivo de cotización no debe superar los 10MB',
            'proveedor_id.required'           => 'Debe seleccionar un proveedor',
            'proveedor_id.exists'             => 'El proveedor seleccionado no existe',
            'empresa_construcc_id.exists'     => 'La empresa de construcción seleccionada no existe',
            'empresa.required'                => 'El nombre de la empresa es obligatorio',
            'empresa.max'                     => 'El nombre de la empresa no debe exceder 255 caracteres',
            'residente.required'              => 'El nombre del residente es obligatorio',
            'residente.max'                   => 'El nombre del residente no debe exceder 255 caracteres',
            'cotizacion_id.integer'           => 'El ID de cotización debe ser un número entero',
            'monto_total.required'            => 'El monto total es obligatorio',
            'monto_total.numeric'             => 'El monto total debe ser un número válido',
            'monto_total.min'                 => 'El monto total no puede ser negativo',
        ];
    }
}
