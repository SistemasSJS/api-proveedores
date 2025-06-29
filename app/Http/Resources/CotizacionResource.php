<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CotizacionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'requisicion_id' => $this->requisicion_id,
            'fecha_cotizacion' => $this->fecha_cotizacion,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'total' => $this->total,
            'observaciones' => $this->observaciones,
            'detalles' => CotizacionDetalleResource::collection($this->whenLoaded('detalles')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
