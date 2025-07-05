<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedidoDetalleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cantidad_confirmada' => $this->cantidad_confirmada,
            'precio_unitario_final' => $this->precio_unitario_final,
            'subtotal' => $this->subtotal,
            'descuento_unitario' => $this->descuento_unitario,
            'descuento_total' => $this->descuento_total,
            'observaciones' => $this->observaciones,
            
            // Control de entrega
            'cantidad_entregada' => $this->cantidad_entregada,
            'cantidad_pendiente' => $this->cantidad_pendiente,
            'entrega_completa' => $this->entrega_completa,
            'porcentaje_entregado' => $this->getPorcentajeEntregado(),
            'estado_entrega' => $this->getEstadoEntrega(),
            'color_estado_entrega' => $this->getColorEstadoEntrega(),
            
            // Relaciones
            'cotizacion_detalle' => new CotizacionDetalleResource($this->whenLoaded('cotizacionDetalle')),
            'producto' => new ProductoResource($this->whenLoaded('producto')),
            
            // Metadatos
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}