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
            'datos_solicitud_pago' => [
                'id' => $this->id,
                'numero_folio_solicitud' => $this->numero_folio_solicitud,
                'folio_sp_consecutivo' => $this->folio_sp_consecutivo,
                'folio_factura' => $this->folio_factura,
                'tiene_factura' => $this->tiene_factura,
                'usuario' => $this->usuario_id,
                'descripcion_concepto' => $this->descripcion_concepto,
                'observaciones' => $this->observaciones,
                'notas' => $this->notas,
                'utilizara' => $this->utilizara,
                'equipo' => $this->equipo,
                'equipo_id' => $this->equipo_id,
                'estado_solicitud' => $this->estado_solicitud,
            ],

            // Campos de tipo y origen
            'tipo' => $this->tipo,
            'tipo_id' => $this->tipo_id,
            'obra_id' => $this->obra_id,
            'observaciones' => $this->observaciones,
            'notas' => $this->notas,
            'utilizara' => $this->utilizara,
            'equipo' => $this->equipo,

            // Campos de aprobación
            'dg' => $this->dg?->value,
            'dg_fecha' => $this->dg_fecha?->format('Y-m-d H:i:s'),

            'dt' => $this->dt?->value,
            'dt_fecha' => $this->dt_fecha?->format('Y-m-d H:i:s'),

            'pc' => $this->pc?->value,
            'pc_fecha' => $this->pc_fecha?->format('Y-m-d H:i:s'),

            'si' => $this->si?->value,
            'si_fecha' => $this->si_fecha?->format('Y-m-d H:i:s'),

            'da' => $this->da?->value,
            'da_fecha' => $this->da_fecha?->format('Y-m-d H:i:s'),

            'ro' => $this->ro?->value,
            'ro_fecha' => $this->ro_fecha?->format('Y-m-d H:i:s'),

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
