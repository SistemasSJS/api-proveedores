<?php

namespace App\Http\Requests\Cotizacion;

use Illuminate\Foundation\Http\FormRequest;

class CotizacionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha_cotizacion' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_cotizacion',
            'total' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:1000',
            'estatus' => 'required|in:pendiente,aprobada,rechazada',
        ];
    }

    public function messages()
    {
        return [
            'proveedor_id.required' => 'Debe seleccionar un proveedor',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe',
            'fecha_cotizacion.required' => 'La fecha de cotización es obligatoria',
            'fecha_cotizacion.date' => 'La fecha de cotización debe ser una fecha válida',
            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria',
            'fecha_vencimiento.date' => 'La fecha de vencimiento debe ser una fecha válida',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento no puede ser anterior a la fecha de cotización',
            'total.required' => 'El total es obligatorio',
            'total.numeric' => 'El total debe ser un número válido',
            'total.min' => 'El total no puede ser negativo',
            'observaciones.string' => 'Las observaciones deben ser texto',
            'observaciones.max' => 'Las observaciones no deben exceder 1000 caracteres',
            'estatus.required' => 'El estatus es obligatorio',
            'estatus.in' => 'El estatus debe ser: pendiente, aprobada o rechazada',
        ];
    }
}
