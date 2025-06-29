<?php

namespace App\Http\Requests\Sucursal;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actualizaciones' => 'required|array|min:1|max:200',
            'actualizaciones.*.producto_id' => 'required|exists:productos,id',
            'actualizaciones.*.stock_local' => 'required|integer|min:0|max:99999',
            'actualizaciones.*.precio_local' => 'nullable|numeric|min:0|max:999999.99',
            'actualizaciones.*.activo' => 'boolean',
            'motivo' => 'nullable|string|max:500',
            'aplicar_descuento' => 'nullable|boolean',
            'porcentaje_descuento' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'actualizaciones.required' => 'Debe incluir al menos una actualización.',
            'actualizaciones.max' => 'No puede actualizar más de 200 productos a la vez.',
            'actualizaciones.*.producto_id.required' => 'El ID del producto es obligatorio.',
            'actualizaciones.*.producto_id.exists' => 'Uno o más productos no existen.',
            'actualizaciones.*.stock_local.required' => 'El stock local es obligatorio.',
            'actualizaciones.*.stock_local.max' => 'El stock no puede exceder 99,999 unidades.',
            'motivo.max' => 'El motivo no puede exceder 500 caracteres.',
            'porcentaje_descuento.max' => 'El descuento no puede ser mayor al 100%.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $sucursal = $this->route('sucursal');
            $productosIds = collect($this->input('actualizaciones', []))->pluck('producto_id');
            
            // Verificar que los productos estén asignados a la sucursal
            $productosAsignados = $sucursal->productos()->whereIn('producto_id', $productosIds)->pluck('producto_id');
            
            if ($productosAsignados->count() !== $productosIds->count()) {
                $validator->errors()->add('actualizaciones', 'Algunos productos no están asignados a esta sucursal.');
            }
        });
    }
}