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

    public function messages(): array
    {
        return [
            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
            'detalles.required' => 'Debe incluir al menos un detalle de cotización.',
            'detalles.*.cantidad_cotizada.required' => 'La cantidad cotizada es obligatoria.',
            'detalles.*.precio_unitario.required' => 'El precio unitario es obligatorio.',
            'detalles.*.tiempo_entrega_dias.required' => 'El tiempo de entrega es obligatorio.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que todos los detalles pertenezcan a la requisición
            $requisicion = $this->route('requisicion');
            $detallesIds = collect($this->input('detalles', []))->pluck('requisicion_detalle_id');

            $detallesValidos = $requisicion->detalles()->whereIn('id', $detallesIds)->pluck('id');

            if ($detallesValidos->count() !== $detallesIds->count()) {
                $validator->errors()->add('detalles', 'Algunos detalles no pertenecen a esta requisición.');
            }
        });
    }
}
