<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RequisicionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'numero_requisicion' => $this->numero_requisicion,
            'usuario_id' => $this->usuario_id,
            'proveedor_id' => $this->proveedor_id,
            'estatus' => $this->estatus,
            'fecha_requerida' => $this->fecha_requerida,
            'fecha_cancelacion' => $this->fecha_cancelacion,
            'motivo_cancelacion' => $this->motivo_cancelacion,
            'observaciones' => $this->observaciones,
            'observaciones_proveedor' => $this->observaciones_proveedor,
            'total_estimado' => $this->total_estimado,
            'usuario' => new UserResource($this->whenLoaded('usuario')),
            'proveedor' => new ProveedorResource($this->whenLoaded('proveedor')),
            'detalles' => RequisicionDetalleResource::collection($this->whenLoaded('detalles')),
            'cotizacion' => new CotizacionResource($this->whenLoaded('cotizacion')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


