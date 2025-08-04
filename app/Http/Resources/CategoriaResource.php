<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id'          => $this->id,
      'nombre'      => $this->nombre,
      'subcategorias' => $this->whenLoaded('children'),  // Aquí cargamos las subcategorías si están disponibles
      'categoria_padre' => $this->whenLoaded('parent', function () {
        return [
          'id'     => $this->parent->id,   // Usamos 'parent' que es la relación definida en el modelo
          'nombre' => $this->parent->nombre,
        ];
      }),
    ];
  }
}
