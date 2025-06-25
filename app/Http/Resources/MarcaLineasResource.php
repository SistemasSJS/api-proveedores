<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarcaLineasResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id'         => $this->id,
      'nombre'     => $this->nombre,
      'logo'       => asset('storage/' . $this->logo),
      'lineas'      => $this->whenLoaded("lineas", function () {
        return [
          'id'         => $this->lineas->id,
          'nombre'     => $this->lineas->nombre,
        ];
      }),
    ];
  }
}
