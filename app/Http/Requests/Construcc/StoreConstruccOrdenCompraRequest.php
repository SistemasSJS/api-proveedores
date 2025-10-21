<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class StoreConstruccOrdenCompraRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'numero_orden' => 'required|string|max:255|unique:ordenes_compra,numero_orden',
            'fecha_orden' => 'required|date',
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'empresa_construcc_id' => 'required|integer|exists:empresa_construcc,id',
            'importe_total' => 'required|numeric|min:0.01',
            'estado' => 'nullable|string|in:pendiente,aprobada,rechazada,completada,cancelada',
            'fecha_aprobacion' => 'nullable|date',
            'observaciones' => 'nullable|string|max:1000',
            'metadata_json' => 'nullable|array',
            'detalles' => 'nullable|array|min:1',
            'detalles.*.producto' => 'required_with:detalles|string|max:255',
            'detalles.*.descripcion' => 'nullable|string|max:500',
            'detalles.*.cantidad' => 'required_with:detalles|numeric|min:0.001',
            'detalles.*.unidad_medida' => 'nullable|string|max:50',
            'detalles.*.precio_unitario' => 'required_with:detalles|numeric|min:0.01',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'numero_orden' => 'número de orden',
            'fecha_orden' => 'fecha de orden',
            'proveedor_id' => 'proveedor',
            'empresa_construcc_id' => 'empresa constructora',
            'importe_total' => 'importe total',
            'fecha_aprobacion' => 'fecha de aprobación',
            'detalles' => 'detalles',
            'detalles.*.producto' => 'producto',
            'detalles.*.descripcion' => 'descripción',
            'detalles.*.cantidad' => 'cantidad',
            'detalles.*.unidad_medida' => 'unidad de medida',
            'detalles.*.precio_unitario' => 'precio unitario',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero_orden.unique' => 'Ya existe una orden de compra con este número.',
            'numero_orden.required' => 'El número de orden es requerido.',
            'fecha_orden.required' => 'La fecha de orden es requerida.',
            'proveedor_id.required' => 'El proveedor es requerido.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'empresa_construcc_id.required' => 'La empresa constructora es requerida.',
            'empresa_construcc_id.exists' => 'La empresa constructora seleccionada no existe.',
            'importe_total.required' => 'El importe total es requerido.',
            'importe_total.min' => 'El importe total debe ser mayor a 0.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'detalles.min' => 'Debe incluir al menos un detalle.',
            'detalles.*.producto.required_with' => 'El producto es requerido en cada detalle.',
            'detalles.*.cantidad.required_with' => 'La cantidad es requerida en cada detalle.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'detalles.*.precio_unitario.required_with' => 'El precio unitario es requerido en cada detalle.',
            'detalles.*.precio_unitario.min' => 'El precio unitario debe ser mayor a 0.',
        ];
    }

    // /**
    //  * Configure the validator instance.
    //  */
    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         // Validar que el importe total coincida con la suma de los detalles si hay detalles
    //         if ($this->has('detalles') && is_array($this['detalles'])) {
    //             $importeCalculado = collect($this['detalles'])
    //                 ->sum(fn($detalle) => ($detalle['cantidad'] ?? 0) * ($detalle['precio_unitario'] ?? 0));

    //             if (abs($importeCalculado - ($this->importe_total ?? 0)) > 0.01) {
    //                 $validator->errors()->add('importe_total', 'El importe total no coincide con la suma de los detalles.');
    //             }
    //         }
    //     });
    // }
}
