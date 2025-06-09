<?php

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;

class ProductoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:100'],
            'descripcion' => ['sometimes', 'required', 'string', 'max:255'],
            'codigo_interno' => ['sometimes', 'required', 'string', 'max:50'],
            'proveedor_id' => ['sometimes', 'required', 'integer', 'exists:proveedores,id'],
            'unidad_medida_id' => ['sometimes', 'required', 'integer', 'exists:unidad_medidas,id'],
            'grupo_id' => ['sometimes', 'required', 'integer', 'exists:grupos,id'],
            'categoria_id' => ['sometimes', 'required', 'integer', 'exists:categorias,id'],
            'marca_id' => ['sometimes', 'required', 'integer', 'exists:marcas,id'],
            'linea_id' => ['sometimes', 'required', 'integer', 'exists:lineas,id'],
            'foto_url' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'codigo_interno.required' => 'El código interno es obligatorio.',
            'proveedor_id.required' => 'El proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
            'unidad_medida_id.required' => 'La unidad de medida es obligatoria.',
            'unidad_medida_id.exists' => 'La unidad de medida seleccionada no es válida.',
            'grupo_id.required' => 'El grupo es obligatorio.',
            'grupo_id.exists' => 'El grupo seleccionado no es válido.',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
            'marca_id.required' => 'La marca es obligatoria.',
            'marca_id.exists' => 'La marca seleccionada no es válida.',
            'linea_id.required' => 'La línea es obligatoria.',
            'linea_id.exists' => 'La línea seleccionada no es válida.',
        ];
    }
}
