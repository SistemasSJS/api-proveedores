<?php

namespace App\Http\Requests\ProveedorImportProducto;

use Illuminate\Foundation\Http\FormRequest;

class ProductoBulkStoreRequest extends FormRequest
{
  public function rules()
  {
    return [
      'productos' => 'required|array|min:1',
      'productos.*.codigo' => 'required|string|max:255',
      'productos.*.producto' => 'required|string|max:255',
      'productos.*.descripcion' => 'nullable|string',
      'productos.*.marca' => 'nullable|string|max:255',
      'productos.*.categoria' => 'nullable|string|max:255',
      'productos.*.subcategoria' => 'nullable|string|max:255',
      'productos.*.modelo' => 'nullable|string|max:255',
      'productos.*.unidad_medida' => 'nullable|string|max:255',
      'productos.*.precio' => 'required|numeric|min:0',
      'productos.*.precio_mayoreo' => 'nullable|numeric|min:0',
      'productos.*.precio_menudeo' => 'nullable|numeric|min:0',
    ];
  }

  public function messages()
  {
    return [
      'productos.required' => 'Se requiere al menos un producto.',
      'productos.array' => 'El campo productos debe ser un arreglo válido.',
      'productos.min' => 'Debes enviar al menos un producto.',

      'productos.*.codigo.required' => 'El campo código es obligatorio para cada producto.',
      'productos.*.codigo.string' => 'El código debe ser una cadena de texto.',
      'productos.*.codigo.max' => 'El código no debe exceder los 255 caracteres.',

      'productos.*.producto.required' => 'El nombre del producto es obligatorio.',
      'productos.*.producto.string' => 'El nombre del producto debe ser una cadena de texto.',
      'productos.*.producto.max' => 'El nombre del producto no debe exceder los 255 caracteres.',

      'productos.*.descripcion.string' => 'La descripción debe ser una cadena de texto.',

      'productos.*.marca.string' => 'La marca debe ser una cadena de texto.',
      'productos.*.marca.max' => 'La marca no debe exceder los 255 caracteres.',

      'productos.*.categoria.string' => 'La categoría debe ser una cadena de texto.',
      'productos.*.categoria.max' => 'La categoría no debe exceder los 255 caracteres.',

      'productos.*.subcategoria.string' => 'La subcategoría debe ser una cadena de texto.',
      'productos.*.subcategoria.max' => 'La subcategoría no debe exceder los 255 caracteres.',

      'productos.*.modelo.string' => 'El modelo debe ser una cadena de texto.',
      'productos.*.modelo.max' => 'El modelo no debe exceder los 255 caracteres.',

      'productos.*.unidad_medida.string' => 'La unidad de medida debe ser una cadena de texto.',
      'productos.*.unidad_medida.max' => 'La unidad de medida no debe exceder los 255 caracteres.',

      'productos.*.precio.required' => 'El precio es obligatorio.',
      'productos.*.precio.numeric' => 'El precio debe ser un número.',
      'productos.*.precio.min' => 'El precio debe ser al menos 0.',

      'productos.*.precio_mayoreo.numeric' => 'El precio de mayoreo debe ser un número.',
      'productos.*.precio_mayoreo.min' => 'El precio de mayoreo debe ser al menos 0.',

      'productos.*.precio_menudeo.numeric' => 'El precio de menudeo debe ser un número.',
      'productos.*.precio_menudeo.min' => 'El precio de menudeo debe ser al menos 0.',
    ];
  }
}
