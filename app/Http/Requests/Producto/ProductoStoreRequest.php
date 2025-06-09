<?php

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;

class ProductoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string', 'max:255'],
            'codigo_interno' => ['required', 'string', 'max:50'],
            'catalogo_id' => ['required', 'integer', 'exists:catalogos,id'],
            'unidad_medida_id' => ['required', 'integer', 'exists:unidad_medidas,id'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'marca_id' => ['required', 'integer', 'exists:marcas,id'],
            'linea_id' => ['required', 'integer', 'exists:lineas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'codigo_interno.required' => 'El código interno es obligatorio.',
            'catalogo_id.required' => 'El catalogo es obligatorio.',
            'catalogo_id.exists' => 'El catalogo seleccionado no es válido.',
            'unidad_medida_id.required' => 'La unidad de medida es obligatoria.',
            'unidad_medida_id.exists' => 'La unidad de medida seleccionada no es válida.',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
            'marca_id.required' => 'La marca es obligatoria.',
            'marca_id.exists' => 'La marca seleccionada no es válida.',
            'linea_id.required' => 'La línea es obligatoria.',
            'linea_id.exists' => 'La línea seleccionada no es válida.',
        ];
    }
}
