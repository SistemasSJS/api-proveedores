<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ElementoImportadoResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   */
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'nombre' => $this->nombre,
      'descripcion' => $this->descripcion ?? null,
      'clave' => $this->clave ?? null,
      'parent_id' => $this->parent_id ?? null,
    ];
  }
}
