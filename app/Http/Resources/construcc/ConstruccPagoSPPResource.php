<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConstruccPagoSPPResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $montoTotal     = (float) $this->monto_total;
        $montoPagado    = (float) ($this->total_pagado ?? 0);
        $montoAutorizado = (float) ($this->monto_autorizado ?? 0);

        return [
            'id' => $this->id,
            'folio_sp_consecutivo'   => $this->folio_sp_consecutivo,
            'numero_folio_solicitud' => $this->numero_folio_solicitud,
            'descripcion_concepto'   => $this->descripcion_concepto,

            // Montos (nombres EXACTOS como en tu interface)
            'monto_total'      => $montoTotal,
            'monto_pagado'     => $montoTotal - $this->calcularSaldoRestante(),
            'monto_pendiente'  => $this->calcularSaldoRestante(),
            'monto_autorizado' => $montoAutorizado,

            // Campos de autorización parcial (si existen)
            'usuario_autorizo_parcial_id'     => $this->usuario_autorizo_parcial_id ?? null,
            'usuario_autorizo_parcial_nombre' => $this->usuario_autorizo_parcial_nombre ?? null,
            'motivo_autorizacion_parcial'     => $this->motivo_autorizacion_parcial ?? null,
            'fecha_autorizacion_parcial'      => optional($this->fecha_autorizacion_parcial)?->format('Y-m-d H:i:s'),

            // Extra útiles para UI
            'proveedor' => $this->whenLoaded('proveedor', function () {
                return [
                    'id' => $this->proveedor->id,
                    'nombre' => $this->proveedor->nombre_comercial,
                ];
            }),



            'fecha_registro' => optional($this->created_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
