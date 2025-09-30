<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class ProveedoresFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el middleware auth:sanctum
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'buscar' => 'nullable|string|min:2|max:255',
            'estado' => 'nullable|string|min:2|max:100',
            'municipio' => 'nullable|string|min:2|max:100',
            'tipos_empresa_id' => 'nullable|string|regex:/^[\d,]+$/', // Formato: "1,2,3"
            'con_productos' => 'nullable|boolean',
            'sort_by' => 'nullable|in:id,nombre_comercial,razon_social,created_at,updated_at',
            'order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:5|max:100',
            'page' => 'nullable|integer|min:1',
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
            'buscar' => 'término de búsqueda',
            'estado' => 'estado',
            'municipio' => 'municipio',
            'tipos_empresa_id' => 'tipos de empresa',
            'con_productos' => 'filtro con productos',
            'sort_by' => 'ordenar por',
            'order' => 'dirección de ordenamiento',
            'per_page' => 'elementos por página',
            'page' => 'página',
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
            'buscar.min' => 'El término de búsqueda debe tener al menos 2 caracteres.',
            'buscar.max' => 'El término de búsqueda no puede exceder 255 caracteres.',
            'estado.min' => 'El estado debe tener al menos 2 caracteres.',
            'municipio.min' => 'El municipio debe tener al menos 2 caracteres.',
            'tipos_empresa_id.regex' => 'Los tipos de empresa deben tener el formato: 1,2,3',
            'sort_by.in' => 'El campo de ordenamiento no es válido.',
            'order.in' => 'La dirección de ordenamiento debe ser asc o desc.',
            'per_page.min' => 'Mínimo 5 elementos por página.',
            'per_page.max' => 'Máximo 100 elementos por página.',
            'page.min' => 'El número de página debe ser mayor a 0.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir string 'true'/'false' a boolean para con_productos
        if ($this->has('con_productos')) {
            $this->merge([
                'con_productos' => filter_var($this->con_productos, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        }

        // Asegurar que per_page tenga un valor por defecto
        if (!$this->has('per_page') || !$this->per_page) {
            $this->merge(['per_page' => 20]);
        }

        // Asegurar que page tenga un valor por defecto
        if (!$this->has('page') || !$this->page) {
            $this->merge(['page' => 1]);
        }
    }
}
