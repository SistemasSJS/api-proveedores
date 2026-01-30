<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ConstruccPagosProveedorResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  Request  $request
   * @return array
   */
  public function toArray($request): array
  {
    $count = (int) ($this->spp_autorizadas_count ?? 0);

    return [
      'id' => $this->id,

      'nombre_comercial' =>  $this->nombre_comercial,

      // Contadores
      'spp_autorizadas_count' => (int) ($this->spp_autorizadas_count ?? 0),
    ];
  }
}
