<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoCatalogoResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'sku' => $this->sku,
      'nombre' => $this->nombre,
      'descripcion' => $this->descripcion,
      'precio_base' => $this->precio_base,
      'stock' => $this->stock,
      'imagen_principal' => $this->imagen_principal,
      'activo' => $this->activo,
      'proveedor' => [
        'id' => $this->proveedor->id,
        'nombre_comercial' => $this->proveedor->nombre_comercial,
        'logo' => $this->proveedor->logo,
      ],
      'marca' => $this->when($this->marca, [
        'id' => $this->marca?->id,
        'nombre' => $this->marca?->nombre,
      ]),
      'linea' => $this->when($this->linea, [
        'id' => $this->linea?->id,
        'nombre' => $this->linea?->nombre,
      ]),
      'categoria' => $this->when($this->categoria, [
        'id' => $this->categoria?->id,
        'nombre' => $this->categoria?->nombre,
      ]),
      'unidad_medida' => $this->when($this->unidadMedida, [
        'id' => $this->unidadMedida?->id,
        'nombre' => $this->unidadMedida?->nombre,
        'abreviatura' => $this->unidadMedida?->abreviatura,
      ]),
    ];
  }
}
