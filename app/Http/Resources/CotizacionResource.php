<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CotizacionResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id'                => $this->id,
      'proveedor_id'      => $this->proveedor_id,
      'proveedor'         => $this->proveedor ? $this->proveedor->nombre : null,
      'fecha_cotizacion'  => $this->fecha_cotizacion?->format('Y-m-d H:i:s'),
      'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),
      'total'             => number_format($this->total, 2),
      'observaciones'     => $this->observaciones,
      'estatus'           => $this->estatus,
      'detalles'          => CotizacionDetalleResource::collection($this->whenLoaded('detalles')),
      'created_at'        => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'        => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
