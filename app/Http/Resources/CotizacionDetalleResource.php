<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CotizacionDetalleResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id'             => $this->id,
      'cotizacion_id'  => $this->cotizacion_id,
      'descripcion'    => $this->descripcion,
      'cantidad'       => $this->cantidad,
      'precio_unitario' => number_format($this->precio_unitario, 2),
      'subtotal'       => number_format($this->subtotal, 2),
      'created_at'     => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'     => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
