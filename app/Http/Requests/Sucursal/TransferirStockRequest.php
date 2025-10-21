<?php

namespace App\Http\Requests\Sucursal;

use Illuminate\Foundation\Http\FormRequest;

class TransferirStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sucursal_origen_id' => 'required|exists:sucursales,id',
            'sucursal_destino_id' => 'required|exists:sucursales,id|different:sucursal_origen_id',
            'transferencias' => 'required|array|min:1|max:50',
            'transferencias.*.producto_id' => 'required|exists:productos,id',
            'transferencias.*.cantidad' => 'required|integer|min:1|max:9999',
            'motivo' => 'required|string|max:500',
            'fecha_transferencia' => 'nullable|date|before_or_equal:today',
            'requiere_aprobacion' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'sucursal_origen_id.required' => 'La sucursal origen es obligatoria.',
            'sucursal_destino_id.required' => 'La sucursal destino es obligatoria.',
            'sucursal_destino_id.different' => 'La sucursal destino debe ser diferente a la origen.',
            'transferencias.required' => 'Debe incluir al menos una transferencia.',
            'transferencias.max' => 'No puede transferir más de 50 productos a la vez.',
            'transferencias.*.cantidad.min' => 'La cantidad mínima a transferir es 1.',
            'transferencias.*.cantidad.max' => 'La cantidad máxima a transferir es 9,999.',
            'motivo.required' => 'El motivo de la transferencia es obligatorio.',
            'motivo.max' => 'El motivo no puede exceder 500 caracteres.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Verificar que ambas sucursales pertenezcan al mismo proveedor
            $origenProveedor = \App\Models\Sucursal::find($this->sucursal_origen_id)?->proveedor_id;
            $destinoProveedor = \App\Models\Sucursal::find($this->sucursal_destino_id)?->proveedor_id;

            if ($origenProveedor !== $destinoProveedor) {
                $validator->errors()->add('sucursal_destino_id', 'Las sucursales deben pertenecer al mismo proveedor.');
            }
        });
    }
}
