<?php

namespace App\Http\Requests\Pedido;

use Illuminate\Foundation\Http\FormRequest;

class PedidoUpdateRequest extends FormRequest
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
            'estatus' => 'required|in:confirmado,en_preparacion,listo_para_entrega,en_transito,entregado,facturado,cancelado',
            'fecha_entrega_estimada' => 'nullable|date|after_or_equal:today',
            'fecha_entrega_real' => 'nullable|date',
            'observaciones' => 'nullable|string|max:1000',
            'observaciones_entrega' => 'nullable|string|max:1000',
            'numero_guia' => 'nullable|string|max:50',
            'transportista' => 'nullable|string|max:100',
            'motivo_cancelacion' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'estatus.required' => 'El estatus es requerido',
            'estatus.in' => 'El estatus seleccionado no es válido',
            'fecha_entrega_estimada.date' => 'La fecha de entrega debe ser una fecha válida',
            'fecha_entrega_estimada.after_or_equal' => 'La fecha de entrega debe ser hoy o posterior',
            'fecha_entrega_real.date' => 'La fecha de entrega real debe ser una fecha válida',
            'observaciones.max' => 'Las observaciones no pueden exceder 1000 caracteres',
            'observaciones_entrega.max' => 'Las observaciones de entrega no pueden exceder 1000 caracteres',
            'numero_guia.max' => 'El número de guía no puede exceder 50 caracteres',
            'transportista.max' => 'El transportista no puede exceder 100 caracteres',
            'motivo_cancelacion.max' => 'El motivo de cancelación no puede exceder 1000 caracteres',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $pedido = $this->route('pedido');
            
            if ($pedido && $this->estatus) {
                // Validar transición de estatus
                if (!$pedido->puedeActualizarEstatus($this->estatus)) {
                    $validator->errors()->add('estatus', 'No se puede cambiar al estatus seleccionado desde el estado actual');
                }
                
                // Validar campos requeridos según estatus
                if ($this->estatus === 'cancelado' && !$this->motivo_cancelacion) {
                    $validator->errors()->add('motivo_cancelacion', 'El motivo de cancelación es requerido');
                }
                
                if ($this->estatus === 'en_transito' && !$this->numero_guia) {
                    $validator->errors()->add('numero_guia', 'El número de guía es requerido para pedidos en tránsito');
                }
                
                if ($this->estatus === 'entregado' && !$this->fecha_entrega_real) {
                    $validator->errors()->add('fecha_entrega_real', 'La fecha de entrega real es requerida');
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
            'estatus' => 'estatus',
            'fecha_entrega_estimada' => 'fecha de entrega estimada',
            'fecha_entrega_real' => 'fecha de entrega real',
            'observaciones' => 'observaciones',
            'observaciones_entrega' => 'observaciones de entrega',
            'numero_guia' => 'número de guía',
            'transportista' => 'transportista',
            'motivo_cancelacion' => 'motivo de cancelación',
        ];
    }
}