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
            'sku' => ['required', 'string', 'max:50'],
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'unidad_medida_id' => ['required', 'integer', 'exists:unidad_medidas,id'],
            // Aquí se espera un arreglo de categorías
            'categorias' => ['required', 'array', 'min:1'],
            'categorias.*' => ['integer', 'exists:categorias,id'],

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

            'proveedor_id.required' => 'El proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',

            'unidad_medida_id.required' => 'La unidad de medida es obligatoria.',
            'unidad_medida_id.exists' => 'La unidad de medida seleccionada no es válida.',


            'categorias.required' => 'Las categorías son obligatorias.',
            'categorias.array' => 'Las categorías deben enviarse como un arreglo.',
            'categorias.min' => 'Debe seleccionar al menos una categoría.',
            'categorias.*.integer' => 'Cada categoría debe ser un identificador válido.',
            'categorias.*.exists' => 'Una o más categorías seleccionadas no son válidas.',

            'marca_id.required' => 'La marca es obligatoria.',
            'marca_id.exists' => 'La marca seleccionada no es válida.',
            'linea_id.required' => 'La línea es obligatoria.',
            'linea_id.exists' => 'La línea seleccionada no es válida.',
        ];
    }
}
