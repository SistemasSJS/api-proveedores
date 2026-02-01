<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConstruccPagoResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      // datos de empresa construcc
      'empresa_construcc_id' => $this->empresa_construcc_id,
      'empresa_construcc_nombre' => $this->whenLoaded('empresaConstrucc', fn() => $this->empresaConstrucc->nombre),
      'usuario_id' => $this->usuario_registro_id,
      'usuario_nombre' => $this->usuario_registro_nombre,
      // proveedor
      'proveedor_id' => $this->whenLoaded('proveedor', fn() => $this->proveedor->id),
      'proveedor_nombre_comercial' => $this->whenLoaded('proveedor', fn() => $this->proveedor->nombre_comercial),
      'proveedor_razon_social' => $this->whenLoaded('proveedor', fn() => $this->proveedor->razon_social),
      'proveedor_rfc' => $this->whenLoaded('proveedor', fn() => $this->proveedor->rfc),

      // datos del comprobante de pago
      'datos_comprobante' => [
        // usnado la ruta de descarga del comprobante
        'comprobante_url' => $this->when($this->comprobante_pago, fn() => route('construcc.pagos-spp.proveedor.spp.descargar-comprobante', ['proveedor' => $this->proveedor_id, 'pago' => $this->id])),
        'monto_total' => $this->monto_total,
        'fecha_registro' => $this->fecha_registro,
        'fecha_pago' => $this->fecha_pago,
        'referencia_pago' => $this->referencia_pago,
        'banco_destino' => $this->banco_destino,
        'titular_cuenta_destino' => $this->titular_cuenta_destino,
        'clave_rastreo' => $this->clave_rastreo,
      ],

      // Solicitudes de pago aplicadas (detalle del pivot)
      'solicitudes_pago' => $this->whenLoaded('solicitudesPago', function () {
        return $this->solicitudesPago->map(function ($sp) {
          return [
            'id' => $sp->id,
            'numero_folio_solicitud' => $sp->numero_folio_solicitud ?? null,
            'monto_total_sp' => (float) $sp->monto_total,
            'monto_aplicado' => (float) $sp->pivot->monto_aplicado,
            'estado_pago' => $sp->pivot->estado_pago,
            'notas' => $sp->pivot->notas,
            'fecha_aplicacion' => optional($sp->pivot->fecha_aplicacion)?->toDateTimeString(),
          ];
        });
      }),

      // Fechas
      'fecha_pago' => optional($this->fecha_pago)?->toDateTimeString(),
      'fecha_registro' => optional($this->fecha_registro)?->toDateTimeString(),
      'created_at' => optional($this->created_at)?->toDateTimeString(),
      'updated_at' => optional($this->updated_at)?->toDateTimeString(),
    ];
  }
}
