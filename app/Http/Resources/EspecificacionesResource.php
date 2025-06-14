<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EspecificacionesResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [

      'producto_id' => $this->producto_id,
      'atributo' => $this->atributo,
      'valor' => $this->valor,
      'unidad' => $this->unidad,
      'orden' => $this->orden,
    ];
  }
}
