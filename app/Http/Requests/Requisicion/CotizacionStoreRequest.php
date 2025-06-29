<?php

namespace App\Http\Requests\Requisicion;

use Illuminate\Foundation\Http\FormRequest;

class CotizacionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_vencimiento' => 'required|date|after:today',
            'observaciones' => 'nullable|string|max:500',
            'detalles' => 'required|array|min:1',
            'detalles.*.requisicion_detalle_id' => 'required|exists:requisicion_detalles,id',
            'detalles.*.cantidad_cotizada' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.tiempo_entrega_dias' => 'required|integer|min:1',
            'detalles.*.observaciones' => 'nullable|string|max:200',
        ];
    }
}
