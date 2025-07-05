<?php

namespace App\Http\Requests\Pedido;

use Illuminate\Foundation\Http\FormRequest;

class PedidoStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'cotizacion_id' => 'required|exists:cotizaciones,id',
            'fecha_entrega_estimada' => 'required|date|after_or_equal:today',
            'observaciones' => 'nullable|string|max:1000',
            'detalles' => 'required|array|min:1',
            'detalles.*.cotizacion_detalle_id' => 'required|exists:cotizacion_detalles,id',
            'detalles.*.cantidad_confirmada' => 'required|integer|min:1',
            'detalles.*.precio_unitario_final' => 'required|numeric|min:0',
            'detalles.*.descuento_unitario' => 'nullable|numeric|min:0',
            'detalles.*.observaciones' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'cotizacion_id.required' => 'La cotización es requerida',
            'cotizacion_id.exists' => 'La cotización seleccionada no existe',
            'fecha_entrega_estimada.required' => 'La fecha de entrega es requerida',
            'fecha_entrega_estimada.date' => 'La fecha de entrega debe ser una fecha válida',
            'fecha_entrega_estimada.after_or_equal' => 'La fecha de entrega debe ser hoy o posterior',
            'detalles.required' => 'Los detalles del pedido son requeridos',
            'detalles.array' => 'Los detalles deben ser un array',
            'detalles.min' => 'Debe incluir al menos un producto',
            'detalles.*.cotizacion_detalle_id.required' => 'El ID del detalle de cotización es requerido',
            'detalles.*.cotizacion_detalle_id.exists' => 'El detalle de cotización no existe',
            'detalles.*.cantidad_confirmada.required' => 'La cantidad confirmada es requerida',
            'detalles.*.cantidad_confirmada.integer' => 'La cantidad debe ser un número entero',
            'detalles.*.cantidad_confirmada.min' => 'La cantidad debe ser mayor a 0',
            'detalles.*.precio_unitario_final.required' => 'El precio unitario es requerido',
            'detalles.*.precio_unitario_final.numeric' => 'El precio debe ser numérico',
            'detalles.*.precio_unitario_final.min' => 'El precio debe ser mayor o igual a 0',
            'detalles.*.descuento_unitario.numeric' => 'El descuento debe ser numérico',
            'detalles.*.descuento_unitario.min' => 'El descuento debe ser mayor o igual a 0',
            'detalles.*.observaciones.max' => 'Las observaciones no pueden exceder 500 caracteres',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que la cotización esté vigente
            if ($this->cotizacion_id) {
                $cotizacion = \App\Models\Cotizacion::find($this->cotizacion_id);
                if ($cotizacion && $cotizacion->fecha_vencimiento < now()->toDateString()) {
                    $validator->errors()->add('cotizacion_id', 'La cotización ha vencido');
                }
                
                // Validar que la cotización no tenga ya un pedido
                if ($cotizacion && $cotizacion->pedido()->exists()) {
                    $validator->errors()->add('cotizacion_id', 'Esta cotización ya tiene un pedido asociado');
                }
            }

            // Validar que los detalles pertenezcan a la cotización
            if ($this->detalles && $this->cotizacion_id) {
                $cotizacionDetalleIds = \App\Models\CotizacionDetalle::where('cotizacion_id', $this->cotizacion_id)
                    ->pluck('id')->toArray();
                
                foreach ($this->detalles as $index => $detalle) {
                    if (!in_array($detalle['cotizacion_detalle_id'], $cotizacionDetalleIds)) {
                        $validator->errors()->add(
                            "detalles.{$index}.cotizacion_detalle_id",
                            'El detalle no pertenece a la cotización seleccionada'
                        );
                    }
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'cotizacion_id' => 'cotización',
            'fecha_entrega_estimada' => 'fecha de entrega',
            'observaciones' => 'observaciones',
            'detalles' => 'detalles',
            'detalles.*.cotizacion_detalle_id' => 'detalle de cotización',
            'detalles.*.cantidad_confirmada' => 'cantidad confirmada',
            'detalles.*.precio_unitario_final' => 'precio unitario',
            'detalles.*.descuento_unitario' => 'descuento unitario',
            'detalles.*.observaciones' => 'observaciones del detalle',
        ];
    }
}