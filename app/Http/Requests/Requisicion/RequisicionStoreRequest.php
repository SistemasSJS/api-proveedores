<?php

namespace App\Http\Requests\Requisicion;

use App\Rules\FechaRequeridaValida;
use App\Rules\ProductoBelongsToProveedor;
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
            'fecha_requerida' => ['required', 'date', new FechaRequeridaValida()],
            'observaciones' => 'nullable|string|max:500',
            'productos' => 'required|array|min:1|max:50', // Máximo 50 productos por requisición
            'productos.*.producto_id' => [
                'required',
                'exists:productos,id',
                new ProductoBelongsToProveedor($this->input('proveedor_id'))
            ],
            'productos.*.cantidad' => 'required|integer|min:1|max:9999',
            'productos.*.observaciones' => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
            'fecha_requerida.required' => 'La fecha requerida es obligatoria.',
            'fecha_requerida.date' => 'La fecha requerida debe ser una fecha válida.',
            'productos.required' => 'Debe incluir al menos un producto.',
            'productos.array' => 'Los productos deben enviarse como una lista.',
            'productos.min' => 'Debe incluir al menos un producto.',
            'productos.max' => 'No puede incluir más de 50 productos por requisición.',
            'productos.*.producto_id.required' => 'Cada producto debe tener un ID válido.',
            'productos.*.producto_id.exists' => 'Uno o más productos seleccionados no existen.',
            'productos.*.cantidad.required' => 'La cantidad es obligatoria para cada producto.',
            'productos.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'productos.*.cantidad.min' => 'La cantidad mínima es 1.',
            'productos.*.cantidad.max' => 'La cantidad máxima por producto es 9999.',
            'productos.*.observaciones.max' => 'Las observaciones del producto no pueden exceder 200 caracteres.',
        ];
    }

    protected function prepareForValidation()
    {
        // Normalizar datos antes de validar
        if ($this->has('productos')) {
            $productos = collect($this->input('productos'))->map(function ($producto) {
                return [
                    'producto_id' => (int) $producto['producto_id'],
                    'cantidad' => (int) $producto['cantidad'],
                    'observaciones' => trim($producto['observaciones'] ?? ''),
                ];
            })->toArray();

            $this->merge(['productos' => $productos]);
        }
    }
}
