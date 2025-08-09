<?php

namespace App\Http\Requests\ProveedorImportProducto;

use Illuminate\Foundation\Http\FormRequest;

class ProveedorImportProductoRequest extends FormRequest
{
  public function authorize(): bool
  {
    // Aquí puedes poner tu lógica de autorización
    return true;
  }

  public function rules(): array
  {
    return [
      // === Marcas ===
      'marcas' => ['nullable', 'array'],
      'marcas.*.nombre' => ['required', 'string', 'max:255'],

      // === Unidades de medida ===
      'unidades_medida' => ['nullable', 'array'],
      'unidades_medida.*.nombre' => ['required', 'string', 'max:255'],

      // === Categorías ===
      'categorias' => ['nullable', 'array'],
      'categorias.*.nombre' => ['required', 'string', 'max:255'],

      // === Subcategorías ===
      'subcategorias' => ['nullable', 'array'],
      'subcategorias.*.nombre' => ['required', 'string', 'max:255'],
      'subcategorias.*.categoria_nombre' => ['required', 'string', 'max:255'],

      // === Productos ===
      'productos' => ['required', 'array'],
      'productos.*.codigo' => ['required', 'string', 'max:255'],
      'productos.*.producto' => ['required', 'string', 'max:255'],
      'productos.*.descripcion' => ['nullable', 'string'],
      'productos.*.marca' => ['nullable', 'string', 'max:255'],
      'productos.*.categoria' => ['nullable', 'string', 'max:255'],
      'productos.*.subcategoria' => ['nullable', 'string', 'max:255'],
      'productos.*.modelo' => ['nullable', 'string', 'max:255'],
      'productos.*.unidad_medida' => ['nullable', 'string', 'max:255'],
      'productos.*.precio' => ['nullable', 'numeric', 'min:0'],
      'productos.*.precio_mayoreo' => ['nullable', 'numeric', 'min:0'],
      'productos.*.precio_menudeo' => ['nullable', 'numeric', 'min:0'],
    ];
  }

  public function messages(): array
  {
    return [
      'productos.required' => 'Debe proporcionar al menos un producto para importar.',
      'productos.*.codigo.required' => 'Cada producto debe tener un código.',
      'productos.*.producto.required' => 'Cada producto debe tener un nombre.',
      'productos.*.precio.numeric' => 'El precio debe ser un número válido cuando se proporcione.',
      'productos.*.precio.min' => 'El precio debe ser mayor o igual a 0.',
    ];
  }
}
