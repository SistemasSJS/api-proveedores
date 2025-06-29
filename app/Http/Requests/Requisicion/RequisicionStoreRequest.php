<?php

namespace App\Http\Requests\Requisicion;

use Illuminate\Foundation\Http\FormRequest;

class RequisicionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha_requerida' => 'required|date|after:today',
            'observaciones' => 'nullable|string|max:500',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.observaciones' => 'nullable|string|max:200',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que todos los productos pertenezcan al proveedor
            $proveedorId = $this->input('proveedor_id');
            $productosIds = collect($this->input('productos', []))->pluck('producto_id');

            $productosValidos = Producto::whereIn('id', $productosIds)
                ->where('proveedor_id', $proveedorId)
                ->pluck('id');

            if ($productosValidos->count() !== $productosIds->count()) {
                $validator->errors()->add('productos', 'Algunos productos no pertenecen al proveedor seleccionado.');
            }
        });
    }
}
