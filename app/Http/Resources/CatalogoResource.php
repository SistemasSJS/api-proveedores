<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CatalogoResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'nombre' => $this->nombre,
      'descripcion' => $this->descripcion,
      'photo_path' => $this->photo_path ? url("storage/{$this->photo_path}") : null,
      'proveedor' => new ProveedorResource($this->whenLoaded('proveedor')),
      'created_at' => $this->created_at?->toDateTimeString(),
      'updated_at' => $this->updated_at?->toDateTimeString(),
    ];
  }
}
