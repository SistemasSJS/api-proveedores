<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedidoResource extends JsonResource
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
            'numero_pedido' => $this->numero_pedido,
            'fecha_confirmacion' => $this->fecha_confirmacion?->format('Y-m-d H:i:s'),
            'fecha_entrega_estimada' => $this->fecha_entrega_estimada?->format('Y-m-d'),
            'fecha_entrega_real' => $this->fecha_entrega_real?->format('Y-m-d H:i:s'),
            'estatus' => $this->estatus,
            'estatus_texto' => $this->getEstadoTexto(),
            'estatus_color' => $this->getColorEstatus(),
            'observaciones' => $this->observaciones,
            'observaciones_entrega' => $this->observaciones_entrega,
            'numero_guia' => $this->numero_guia,
            'transportista' => $this->transportista,
            'fecha_cancelacion' => $this->fecha_cancelacion?->format('Y-m-d H:i:s'),
            'motivo_cancelacion' => $this->motivo_cancelacion,

            // Totales
            'subtotal' => $this->subtotal,
            'descuento' => $this->descuento,
            'impuestos' => $this->impuestos,
            'total' => $this->total,

            // Información de estado
            'esta_vencido' => $this->estaVencido(),
            'dias_para_vencimiento' => $this->diasParaVencimiento(),

            // Relaciones
            'requisicion' => new RequisicionResource($this->whenLoaded('requisicion')),
            'cotizacion' => new CotizacionResource($this->whenLoaded('cotizacion')),
            'detalles' => PedidoDetalleResource::collection($this->whenLoaded('detalles')),
            'proveedor' => new ProveedorResource($this->whenLoaded('proveedor')),
            'usuario' => new UserResource($this->whenLoaded('usuario')),

            // Metadatos
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
