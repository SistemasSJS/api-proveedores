<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RequisicionDetalleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'cantidad' => $this->cantidad,
            'precio_unitario_estimado' => $this->precio_unitario_estimado,
            'subtotal_estimado' => $this->subtotal_estimado,
            'observaciones' => $this->observaciones,
            'producto' => new ProductoResource($this->whenLoaded('producto')),
        ];
    }
}
