<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class SugerenciasProductosRequest extends FormRequest
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
            'termino' => 'required|string|min:1|max:255',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'limite' => 'nullable|integer|min:5|max:50',
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
            'termino' => 'término de búsqueda',
            'proveedor_id' => 'proveedor',
            'limite' => 'límite de resultados',
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
            'termino.required' => 'El término de búsqueda es obligatorio.',
            'termino.min' => 'El término de búsqueda debe tener al menos 1 carácter.',
            'termino.max' => 'El término de búsqueda no puede exceder 255 caracteres.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'limite.integer' => 'El límite debe ser un número entero.',
            'limite.min' => 'El límite mínimo es 5 resultados.',
            'limite.max' => 'El límite máximo es 50 resultados.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Establecer límite por defecto si no se proporciona
        if (!$this->has('limite') || !$this->limite) {
            $this->merge(['limite' => 10]);
        }
    }
}
