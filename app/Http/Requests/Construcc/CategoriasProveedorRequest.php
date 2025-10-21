<?php

namespace App\Http\Requests\Construcc;

use Illuminate\Foundation\Http\FormRequest;

class CategoriasProveedorRequest extends FormRequest
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
            'incluir_subcategorias' => 'nullable|boolean',
            'solo_padres' => 'nullable|boolean',
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
            'incluir_subcategorias' => 'incluir subcategorías',
            'solo_padres' => 'solo categorías padre',
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
            'incluir_subcategorias.boolean' => 'El parámetro incluir subcategorías debe ser verdadero o falso.',
            'solo_padres.boolean' => 'El parámetro solo padres debe ser verdadero o falso.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir string 'true'/'false' a boolean
        if ($this->has('incluir_subcategorias')) {
            $this->merge([
                'incluir_subcategorias' => filter_var($this->incluir_subcategorias, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        if ($this->has('solo_padres')) {
            $this->merge([
                'solo_padres' => filter_var($this->solo_padres, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        // Valores por defecto
        $defaults = [];

        if (! $this->has('incluir_subcategorias')) {
            $defaults['incluir_subcategorias'] = false;
        }

        if (! $this->has('solo_padres')) {
            $defaults['solo_padres'] = false;
        }

        if (! empty($defaults)) {
            $this->merge($defaults);
        }
    }
}
