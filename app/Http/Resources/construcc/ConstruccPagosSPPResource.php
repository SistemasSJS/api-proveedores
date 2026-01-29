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
            'id' => $this->id,

            // Folios
            'numero_folio_solicitud' => $this->numero_folio_solicitud,
            'folio_sp_consecutivo'   => $this->folio_sp_consecutivo,

            // Estado
            'estado_solicitud' => $this->estado_solicitud,

            // Montos
            'monto_total'     => $this->monto_total,
            'monto_abonado'   => $this->monto_abonado,
            'saldo_pendiente' => $this->saldo_pendiente,
            'pago_completo'   => $this->pago_completo,

            // Fechas clave
            'fecha_registro' => $this->created_at,
            'fecha_pago'     => $this->fecha_pago,

            // Proveedor (ligero)
            'proveedor' => $this->whenLoaded('proveedor', function () {
                return [
                    'id' => $this->proveedor->id,
                    'nombre_comercial' => $this->proveedor->nombre_comercial,
                ];
            }),

            // Pagos aplicados a esta SPP
            'pagos' => $this->whenLoaded('pagos', function () {
                return $this->pagos->map(function ($pago) {
                    return [
                        'id' => $pago->id,
                        'referencia_pago' => $pago->referencia_pago,
                        'fecha_pago' => $pago->fecha_pago,

                        // Pivot
                        'monto_aplicado' => $pago->pivot->monto_aplicado,
                        'estado_pago'    => $pago->pivot->estado_pago,
                        'notas'          => $pago->pivot->notas,
                        'fecha_aplicacion' => $pago->pivot->fecha_aplicacion,
                    ];
                });
            }),
        ];
    }
}
