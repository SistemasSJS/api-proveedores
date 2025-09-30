<?php

namespace App\Http\Resources\ProveedorDashboard;

use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorDashboardCotizacionResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   */
  public function toArray($request): array
  {
    return [
      'id' => $this->id,
      'fecha_cotizacion' => $this->fecha_cotizacion?->format('Y-m-d'),
      'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),
      'total' => $this->total,
      'estatus' => $this->estatus ?? null,
    ];
  }
}
