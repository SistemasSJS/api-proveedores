<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class ProductosProveedorRequest extends FormRequest
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
            'categoria_id' => 'nullable|string|regex:/^[\d,]+$/',
            'marca_id' => 'nullable|string|regex:/^[\d,]+$/',
            'linea_id' => 'nullable|string|regex:/^[\d,]+$/',
            'con_stock' => 'nullable|boolean',
            'destacado' => 'nullable|boolean',
            'sort_by' => 'nullable|in:id,nombre,precio_base,stock,created_at,updated_at',
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
            'categoria_id' => 'categorías',
            'marca_id' => 'marcas',
            'linea_id' => 'líneas',
            'con_stock' => 'filtro con stock',
            'destacado' => 'filtro destacados',
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
            'categoria_id.regex' => 'Las categorías deben tener el formato: 1,2,3',
            'marca_id.regex' => 'Las marcas deben tener el formato: 1,2,3',
            'linea_id.regex' => 'Las líneas deben tener el formato: 1,2,3',
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
        // Convertir string 'true'/'false' a boolean
        if ($this->has('con_stock')) {
            $this->merge([
                'con_stock' => filter_var($this->con_stock, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        }

        if ($this->has('destacado')) {
            $this->merge([
                'destacado' => filter_var($this->destacado, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        }

        // Valores por defecto
        $defaults = [];
        
        if (!$this->has('sort_by') || !$this->sort_by) {
            $defaults['sort_by'] = 'nombre';
        }
        
        if (!$this->has('order') || !$this->order) {
            $defaults['order'] = 'asc';
        }
        
        if (!$this->has('per_page') || !$this->per_page) {
            $defaults['per_page'] = 20;
        }
        
        if (!$this->has('page') || !$this->page) {
            $defaults['page'] = 1;
        }

        if (!empty($defaults)) {
            $this->merge($defaults);
        }
    }
}
