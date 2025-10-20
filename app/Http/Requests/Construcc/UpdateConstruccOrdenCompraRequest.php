<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConstruccOrdenCompraRequest extends FormRequest
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
            'fecha_orden' => 'sometimes|date',
            'empresa_construcc_id' => 'sometimes|integer|exists:empresas_construcc,id',
            'importe_total' => 'sometimes|numeric|min:0.01',
            'estado' => 'sometimes|string|in:pendiente,aprobada,rechazada,completada,cancelada',
            'fecha_aprobacion' => 'nullable|date',
            'observaciones' => 'nullable|string|max:1000',
            'metadata_json' => 'nullable|array',
            'detalles' => 'sometimes|array|min:1',
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
            'fecha_orden' => 'fecha de orden',
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
            'fecha_orden.date' => 'La fecha de orden debe ser una fecha válida.',
            'empresa_construcc_id.exists' => 'La empresa constructora seleccionada no existe.',
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

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que el importe total coincida con la suma de los detalles si hay detalles
            if ($this->has('detalles') && is_array($this->detalles) && $this->has('importe_total')) {
                $importeCalculado = collect($this->detalles)
                    ->sum(fn ($detalle) => ($detalle['cantidad'] ?? 0) * ($detalle['precio_unitario'] ?? 0));

                if (abs($importeCalculado - ($this->importe_total ?? 0)) > 0.01) {
                    $validator->errors()->add('importe_total', 'El importe total no coincide con la suma de los detalles.');
                }
            }
        });
    }
}