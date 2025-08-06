<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaAcordeonResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id'          => (string) $this->id,
      'nombre'      => $this->nombre,
      'selected'    => false, // Valor por defecto para UI
      'subcategorias' => $this->whenLoaded('children', function () {
        return $this->children->map(function ($subcategoria) {
          return [
            'id'         => (string) $subcategoria->id,
            'nombre'     => $subcategoria->nombre,
            'selected'   => false, // Valor por defecto para UI
            'categoriaId' => (string) $this->id,
          ];
        });
      }),
    ];
  }
}
