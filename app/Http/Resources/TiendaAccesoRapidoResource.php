<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TiendaAccesoRapidoResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param Request $request
   * @return array<string, mixed>
   */
  public function toArray($request)
  {
    return [
      'id'              => $this->id,
      'nombre'          => $this->nombre,
      'icono'           => $this->icono,
      'color'           => $this->color,
      'totalProductos'  => $this->total_productos,
      'tipo'            => $this->tipo,
      'activo'          => (bool) $this->activo,
    ];
  }
}
