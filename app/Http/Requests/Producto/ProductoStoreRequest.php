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
                    if (!Categoria::where('id', $value)->where('proveedor_id', $proveedorId)->exists()) {
                        $fail('La categoria seleccionada no pertenece a este proveedor.');
                    }
                }
            ],
            'marca_id' => [
                'required',
                'integer',
                'exists:marcas,id',
                function ($attribute, $value, $fail) use ($proveedorId) {
                    if (!Marca::where('id', $value)->where('proveedor_id', $proveedorId)->exists()) {
                        $fail('La marca seleccionada no pertenece a este proveedor.');
                    }
                }
            ],
            /**
         * El proveedor viaja de forma implicita en el request.
         * Mediante el se realiza al validacion de permisos...
         * Solo el proveedor tiene la facutad de Gestionar los datos de los
         * catalogos.
         *
         *
         * 'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
         *
         */
            // 'categorias' => ['required', 'array', 'min:1'],
            // 'categorias.*' => ['integer', 'exists:categorias,id'],
            // 'especificaciones' => ['required', 'array', 'min:1'],
            // 'especificaciones.*' => ['integer', 'exists:producto_especificaciones,id'],
            // 'linea_id' => [
            //     'required',
            //     'integer',
            //     'exists:lineas,id',
            //     function ($attribute, $value, $fail) use ($proveedorId) {
            //         $marcaId = $this->input('marca_id');

            //         // Validar que la línea pertenezca al proveedor
            //         if (!Linea::where('id', $value)->where('proveedor_id', $proveedorId)->exists()) {
            //             $fail('La línea seleccionada no pertenece a este proveedor.');
            //             return;
            //         }

            //         // Validar que la línea esté relacionada con la marca seleccionada
            //         if ($marcaId && !Linea::where('id', $value)->where('marca_id', $marcaId)->exists()) {
            //             $fail('La línea seleccionada no pertenece a la marca especificada.');
            //         }
            //     }
            // ],
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
