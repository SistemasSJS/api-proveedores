<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Resources\Json\JsonResource;

class ConstruccCotizacionDetalleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'requisicion_detalle_id' => $this->requisicion_detalle_id,
            'cantidad_cotizada' => $this->cantidad_cotizada,
            'precio_unitario' => $this->precio_unitario,
            'subtotal' => $this->subtotal,
            'tiempo_entrega_dias' => $this->tiempo_entrega_dias,
            'observaciones' => $this->observaciones,
            'requisicion_detalle' => new RequisicionDetalleResource($this->whenLoaded('requisicionDetalle')),
        ];
    }
}
