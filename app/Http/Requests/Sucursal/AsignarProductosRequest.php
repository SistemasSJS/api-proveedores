<?php

namespace App\Http\Requests\Sucursal;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;

class AsignarProductosRequest extends FormRequest
{
  /**
   * Determinar si el usuario está autorizado para hacer esta petición
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Reglas de validación
   */
  public function rules(): array
  {
    return [
      'productos' => 'required|array|min:1|max:100',
      'productos.*.id' => 'required|exists:productos,id',
      'productos.*.stock_local' => 'required|integer|min:0|max:99999',
      'productos.*.precio_local' => 'nullable|numeric|min:0|max:999999.99',
      'productos.*.activo' => 'boolean',
      'productos.*.notas' => 'nullable|string|max:255',
    ];
  }

  /**
   * Mensajes de error personalizados
   */
  public function messages(): array
  {
    return [
      'productos.required' => 'Debe seleccionar al menos un producto.',
      'productos.array' => 'Los productos deben enviarse como una lista.',
      'productos.min' => 'Debe incluir al menos un producto.',
      'productos.max' => 'No puede asignar más de 100 productos a la vez.',

      'productos.*.id.required' => 'Cada producto debe tener un ID válido.',
      'productos.*.id.exists' => 'Uno o más productos seleccionados no existen.',

      'productos.*.stock_local.required' => 'El stock local es obligatorio para cada producto.',
      'productos.*.stock_local.integer' => 'El stock local debe ser un número entero.',
      'productos.*.stock_local.min' => 'El stock local no puede ser negativo.',
      'productos.*.stock_local.max' => 'El stock local no puede exceder 99,999 unidades.',

      'productos.*.precio_local.numeric' => 'El precio local debe ser un número válido.',
      'productos.*.precio_local.min' => 'El precio local no puede ser negativo.',
      'productos.*.precio_local.max' => 'El precio local no puede exceder $999,999.99.',

      'productos.*.activo.boolean' => 'El campo activo debe ser verdadero o falso.',
      'productos.*.notas.max' => 'Las notas no pueden exceder 255 caracteres.',
    ];
  }

  /**
   * Preparar datos antes de la validación
   */
  protected function prepareForValidation()
  {
    // Normalizar datos antes de validar
    if ($this->has('productos')) {
      $productos = collect($this->input('productos'))->map(function ($producto) {
        return [
          'id' => (int) $producto['id'],
          'stock_local' => (int) ($producto['stock_local'] ?? 0),
          'precio_local' => isset($producto['precio_local']) ? (float) $producto['precio_local'] : null,
          'activo' => filter_var($producto['activo'] ?? true, FILTER_VALIDATE_BOOLEAN),
          'notas' => trim($producto['notas'] ?? ''),
        ];
      })->toArray();

      $this->merge(['productos' => $productos]);
    }
  }

  /**
   * Validaciones adicionales después de las reglas básicas
   */
  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      $proveedor = $this->route('proveedor');
      $sucursal = $this->route('sucursal');
      $productosIds = collect($this->input('productos', []))->pluck('id');

      // Verificar que todos los productos pertenezcan al proveedor
      $productosValidos = Producto::whereIn('id', $productosIds)
        ->where('proveedor_id', $proveedor->id)
        ->where('activo', true)
        ->pluck('id');

      if ($productosValidos->count() !== $productosIds->count()) {
        $validator->errors()->add('productos', 'Algunos productos no pertenecen a este proveedor o están inactivos.');
      }

      // Verificar duplicados en la petición
      if ($productosIds->count() !== $productosIds->unique()->count()) {
        $validator->errors()->add('productos', 'No se pueden incluir productos duplicados en la misma asignación.');
      }

      // Verificar productos ya asignados a la sucursal
      $productosYaAsignados = $sucursal->productos()
        ->whereIn('producto_id', $productosIds)
        ->pluck('producto_id');

      if ($productosYaAsignados->isNotEmpty()) {
        $skusAsignados = Producto::whereIn('id', $productosYaAsignados)
          ->pluck('sku')
          ->implode(', ');

        $validator->errors()->add('productos', "Los siguientes productos ya están asignados a esta sucursal: {$skusAsignados}");
      }

      // Validar coherencia de precios
      foreach ($this->input('productos', []) as $index => $productoData) {
        if (isset($productoData['precio_local']) && $productoData['precio_local'] > 0) {
          $producto = Producto::find($productoData['id']);
          if ($producto && $productoData['precio_local'] < ($producto->precio_base * 0.5)) {
            $validator->errors()->add(
              "productos.{$index}.precio_local",
              "El precio local no puede ser menor al 50% del precio base del producto."
            );
          }
        }
      }

      // Validar límite de productos por sucursal
      $productosActuales = $sucursal->productos()->count();
      $nuevosProductos = count($this->input('productos', []));
      $limiteMaximo = 1000; // Límite configurable

      if (($productosActuales + $nuevosProductos) > $limiteMaximo) {
        $validator->errors()->add(
          'productos',
          "La sucursal no puede tener más de {$limiteMaximo} productos asignados."
        );
      }
    });
  }

  /**
   * Obtener datos validados y formateados
   */
  public function getProductosFormateados(): array
  {
    return collect($this->validated()['productos'])->map(function ($producto) {
      return [
        'id' => $producto['id'],
        'stock_local' => $producto['stock_local'],
        'precio_local' => $producto['precio_local'],
        'activo' => $producto['activo'] ?? true,
        'notas' => $producto['notas'] ?? null,
        'fecha_asignacion' => now(),
      ];
    })->toArray();
  }

  /**
   * Obtener resumen de la asignación
   */
  public function getResumenAsignacion(): array
  {
    $productos = $this->validated()['productos'];

    return [
      'total_productos' => count($productos),
      'stock_total' => collect($productos)->sum('stock_local'),
      'valor_estimado' => collect($productos)->sum(function ($p) {
        return $p['stock_local'] * ($p['precio_local'] ?? 0);
      }),
      'productos_con_precio_personalizado' => collect($productos)
        ->where('precio_local', '!=', null)->count(),
      'productos_activos' => collect($productos)
        ->where('activo', true)->count(),
    ];
  }

  /**
   * Atributos personalizados para mensajes de error
   */
  public function attributes(): array
  {
    return [
      'productos' => 'lista de productos',
      'productos.*.id' => 'ID del producto',
      'productos.*.stock_local' => 'stock local',
      'productos.*.precio_local' => 'precio local',
      'productos.*.activo' => 'estado activo',
      'productos.*.notas' => 'notas',
    ];
  }
}
