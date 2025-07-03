<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TiendaProductoDestacadoResource extends JsonResource
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
      'producto' => new TiendaProductoResource($this),
      'motivo' => $this->motivo ?? null,
      'descuento' => $this->descuento ?? null,
      'mensaje' => $this->mensaje ?? null,
    ];
  }
}
