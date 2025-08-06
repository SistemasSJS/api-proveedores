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
    ];
  }
}
