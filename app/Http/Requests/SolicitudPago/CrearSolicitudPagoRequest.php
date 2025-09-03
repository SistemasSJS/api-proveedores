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
            'descripcion_concepto' => 'required|string|min:10',
            'factura_pdf'          => 'required|file|mimes:pdf|max:10240',
            'factura_xml'          => 'required|file|mimes:xml|max:5120',
            'proveedor_id'         => 'required|exists:proveedores,id',
        ];
    }

    public function messages()
    {
        return [
            'descripcion_concepto.required' => 'La descripción del concepto es obligatoria',
            'descripcion_concepto.min'      => 'La descripción debe tener al menos 10 caracteres',
            'factura_pdf.required'          => 'El archivo PDF de la factura es obligatorio',
            'factura_pdf.mimes'             => 'El archivo debe ser un PDF válido',
            'factura_pdf.max'               => 'El archivo PDF no debe superar los 10MB',
            'factura_xml.required'          => 'El archivo XML de la factura es obligatorio',
            'factura_xml.mimes'             => 'El archivo debe ser un XML válido',
            'factura_xml.max'               => 'El archivo XML no debe superar los 5MB',
            'proveedor_id.required'         => 'Debe seleccionar un proveedor',
            'proveedor_id.exists'           => 'El proveedor seleccionado no existe',
        ];
    }
}
