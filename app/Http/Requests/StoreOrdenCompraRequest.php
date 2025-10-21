<?php

namespace App\Http\Requests;

use App\Enums\EstadoOrdenCompra;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrdenCompraRequest extends FormRequest
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
            'numero_orden' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ordenes_compra')->where(function ($query) {
                    return $query->where('proveedor_id', $this->route('proveedor')->id);
                }),
            ],
            'fecha_orden' => 'required|date',
            'empresa_construcc_id' => 'required|integer|exists:empresas_construcc,id',
            'importe_total' => 'required|numeric|min:0.01',
            'estado' => ['required', 'string', Rule::in(EstadoOrdenCompra::values())],
            'fecha_aprobacion' => 'nullable|date|after_or_equal:fecha_orden',
            'observaciones' => 'nullable|string|max:1000',
            'metadata_json' => 'nullable|array',

            // Detalles de la orden
            'detalles' => 'required|array|min:1',
            'detalles.*.producto' => 'required|string|max:255',
            'detalles.*.descripcion' => 'nullable|string|max:500',
            'detalles.*.cantidad' => 'required|numeric|min:0.001',
            'detalles.*.unidad_medida' => 'nullable|string|max:50',
            'detalles.*.precio_unitario' => 'required|numeric|min:0.01',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'numero_orden.unique' => 'Ya existe una orden de compra con este número para este proveedor.',
            'detalles.required' => 'La orden debe incluir al menos un detalle.',
            'detalles.*.producto.required' => 'El producto es requerido en cada detalle.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'detalles.*.precio_unitario.min' => 'El precio unitario debe ser mayor a 0.',
            'fecha_aprobacion.after_or_equal' => 'La fecha de aprobación no puede ser anterior a la fecha de la orden.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que el importe total coincida con la suma de los detalles
            $importeCalculado = collect($this->detalles ?? [])
                ->sum(fn ($detalle) => ($detalle['cantidad'] ?? 0) * ($detalle['precio_unitario'] ?? 0));

            if (abs($importeCalculado - ($this->importe_total ?? 0)) > 0.01) {
                $validator->errors()->add('importe_total', 'El importe total no coincide con la suma de los detalles.');
            }
        });
    }
}
