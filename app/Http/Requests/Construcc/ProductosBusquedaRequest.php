<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class ProductosBusquedaRequest extends FormRequest
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
            'buscar' => 'nullable|string|min:2|max:255',
            'proveedor_id' => 'nullable|string|regex:/^[\d,]+$/',     // Múltiples proveedores: "1,2,3"
            'categoria_id' => 'nullable|string|regex:/^[\d,]+$/',     // Múltiples categorías: "1,2,3"
            'subcategoria_id' => 'nullable|string|regex:/^[\d,]+$/',  // Múltiples subcategorías: "1,2,3"
            'marca_id' => 'nullable|string|regex:/^[\d,]+$/',         // Múltiples marcas: "1,2,3"
            'linea_id' => 'nullable|string|regex:/^[\d,]+$/',         // Múltiples líneas: "1,2,3"
            'unidad_medida_id' => 'nullable|string|regex:/^[\d,]+$/', // Múltiples unidades: "1,2,3"
            'precio_min' => 'nullable|numeric|min:0',
            'precio_max' => 'nullable|numeric|min:0|gt:precio_min',
            'con_stock' => 'nullable|boolean',
            'destacado' => 'nullable|boolean',
            'orden_por' => 'nullable|in:nombre,precio_base,stock,created_at,updated_at',
            'direccion' => 'nullable|in:asc,desc',
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
            'proveedor_id' => 'proveedores',
            'categoria_id' => 'categorías',
            'subcategoria_id' => 'subcategorías',
            'marca_id' => 'marcas',
            'linea_id' => 'líneas',
            'unidad_medida_id' => 'unidades de medida',
            'precio_min' => 'precio mínimo',
            'precio_max' => 'precio máximo',
            'con_stock' => 'filtro con stock',
            'destacado' => 'filtro destacados',
            'orden_por' => 'ordenar por',
            'direccion' => 'dirección de ordenamiento',
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
            'proveedor_id.regex' => 'Los proveedores deben tener el formato: 1,2,3',
            'categoria_id.regex' => 'Las categorías deben tener el formato: 1,2,3',
            'subcategoria_id.regex' => 'Las subcategorías deben tener el formato: 1,2,3',
            'marca_id.regex' => 'Las marcas deben tener el formato: 1,2,3',
            'linea_id.regex' => 'Las líneas deben tener el formato: 1,2,3',
            'unidad_medida_id.regex' => 'Las unidades de medida deben tener el formato: 1,2,3',
            'precio_min.numeric' => 'El precio mínimo debe ser un número.',
            'precio_min.min' => 'El precio mínimo no puede ser negativo.',
            'precio_max.numeric' => 'El precio máximo debe ser un número.',
            'precio_max.min' => 'El precio máximo no puede ser negativo.',
            'precio_max.gt' => 'El precio máximo debe ser mayor al precio mínimo.',
            'orden_por.in' => 'El campo de ordenamiento no es válido.',
            'direccion.in' => 'La dirección de ordenamiento debe ser asc o desc.',
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

        // Convertir precios a números
        if ($this->has('precio_min') && $this->precio_min !== null) {
            $this->merge(['precio_min' => floatval($this->precio_min)]);
        }

        if ($this->has('precio_max') && $this->precio_max !== null) {
            $this->merge(['precio_max' => floatval($this->precio_max)]);
        }

        // Valores por defecto
        $defaults = [];
        
        if (!$this->has('orden_por') || !$this->orden_por) {
            $defaults['orden_por'] = 'nombre';
        }
        
        if (!$this->has('direccion') || !$this->direccion) {
            $defaults['direccion'] = 'asc';
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
