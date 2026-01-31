<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConstruccPagosSPPResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            // Dataos básicos de la SPP
            'id' => $this->id,
            // 'numero_folio_solicitud' => $this->numero_folio_solicitud,
            'folio_sp_consecutivo' => $this->folio_sp_consecutivo,
            'folio_factura' => $this->folio_factura,
            'descripcion_concepto' => $this->descripcion_concepto,
            'estado_solicitud' => $this->estado_solicitud,

            // Campos de tipo y origen
            // 'tipo' => $this->tipo,
            // 'tipo_id' => $this->tipo_id,
            // 'obra_id' => $this->obra_id,
            // 'observaciones' => $this->observaciones,
            // 'notas' => $this->notas,
            // 'utilizara' => $this->utilizara,
            // 'equipo' => $this->equipo,

            // Montos
            'monto_total'     => (float) $this->monto_total, // total de la factura 
            'monto_abonado'   => (float) $this->total_pagado ?? 0, // total abonado hasta la fecha... Para calcular el saldo pendiente sumar pagos
            'monto_pendiente' => (float) $this->saldo_pendiente ?? 0, // monto_total - monto_abonado
            'monto_autorizado' => (float)  $this->monto_autorizado ?? 0,

            // Fechas clave
            'fecha_registro' => $this->created_at->format('Y-m-d H:i:s'),

            // // Pagos aplicados a esta SPP
            // 'pagos' => $this->whenLoaded('pagos', function () {
            //     return $this->pagos->map(function ($pago) {
            //         return [
            //             'id' => $pago->id,
            //             'referencia_pago' => $pago->referencia_pago,
            //             'fecha_pago' => $pago->fecha_pago,

            //             // Pivot
            //             'monto_aplicado' => $pago->pivot->monto_aplicado,
            //             'estado_pago'    => $pago->pivot->estado_pago,
            //             'notas'          => $pago->pivot->notas,
            //             'fecha_aplicacion' => $pago->pivot->fecha_aplicacion,
            //         ];
            //     });
            // }),
        ];
    }
}
