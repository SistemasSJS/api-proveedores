<?php

namespace App\Http\Requests\Producto;

use App\Models\Categoria;
use App\Models\Linea;
use App\Models\Marca;
use Illuminate\Foundation\Http\FormRequest;

class ProductoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $proveedorId = $this->route('proveedor')->id ?? $this->input('proveedor_id');

        return [
            'codigo_interno' => ['required', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string', 'max:255'],
            'unidad_medida_id' => ['required', 'integer', 'exists:unidad_medidas,id'],

            'categoria_id' => [
                'required',
                'integer',
                'exists:categorias,id',
                function ($attribute, $value, $fail) use ($proveedorId) {
                    $categoria = Categoria::delProveedor($proveedorId)->find($value);

                    if (!$categoria) {
                        return $fail('La categoría padre no pertenece al proveedor.');
                    }

                    if ($categoria->nivel !== 0) {
                        return $fail('La categoría padre debe estar en el nivel 0.');
                    }
                }
            ],
            'sub_categoria_id' => [
                'required',
                'integer',
                'exists:categorias,id',
                function ($attribute, $value, $fail) use ($proveedorId) {
                    $subcategoria = Categoria::with('parent')->delProveedor($proveedorId)->find($value);

                    if (!$subcategoria) {
                        return $fail('La subcategoría no pertenece al proveedor.');
                    }

                    $padre = $subcategoria->parent;
                    $categoriaId = $this->input('categoria_id');

                    if (!$padre || $padre->id != $categoriaId) {
                        return $fail('La subcategoría no pertenece a la categoría padre especificada.');
                    }

                    // Validar jerarquía coherente
                    if (($subcategoria->nivel === 1 && $padre->nivel !== 0) ||
                        ($subcategoria->nivel === 2 && $padre->nivel !== 1)
                    ) {
                        return $fail('La jerarquía entre categoría y subcategoría es incorrecta.');
                    }
                }
            ],

            'marca_id' => [
                'required',
                'integer',
                'exists:marcas,id',
                function ($attribute, $value, $fail) use ($proveedorId) {
                    if (!\App\Models\Marca::where('id', $value)->where('proveedor_id', $proveedorId)->exists()) {
                        $fail('La marca seleccionada no pertenece a este proveedor.');
                    }
                }
            ],
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


            'marca_id.exists' => 'La marca seleccionada no es válida o no pertenece a este proveedor.',
            'linea_id.exists' => 'La línea seleccionada no es válida, no pertenece a este proveedor o no está relacionada con la marca.',
        ];
    }
}
