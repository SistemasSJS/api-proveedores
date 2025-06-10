<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id'                 => $this->id,
      'nombre'             => $this->nombre,
      'categoria_padre'    => $this->whenLoaded('categoria_padre', function () {
        return [
          'id'     => $this->categoria_padre->id,
          'nombre' => $this->categoria_padre->nombre,
        ];
      }),
    ];
  }
}
