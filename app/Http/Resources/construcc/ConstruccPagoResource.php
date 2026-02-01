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
      'usuario_id' => $this->usuario_id,
      'usuario_nombre' => $this->usuario_nombre,
      // proveedor
      'proveedor_id' => $this->proveedor_id,
      'proveedor_nombre_comercial' => $this->whenLoaded('proveedor', fn() => $this->proveedor->nombre_comercial),
      'proveedor_razon_social' => $this->whenLoaded('proveedor', fn() => $this->proveedor->razon_social),
      'proveedor_rfc' => $this->whenLoaded('proveedor', fn() => $this->proveedor->rfc),

      // datos del comprobante de pago
      'datos_comprobante' => [
        'monto_total' => $this->monto_total,
        'fecha_registro' => $this->fecha_registro,
        'fecha_pago' => $this->fecha_pago,
        'referencia_pago' => $this->referencia_pago,
        'banco_destino' => $this->banco_destino,
        'titular_cuenta_destino' => $this->titular_cuenta_destino,
        'clave_rastreo' => $this->clave_rastreo,
      ],

      // Fechas
      'fecha_pago' => optional($this->fecha_pago)?->toDateTimeString(),
      'fecha_registro' => optional($this->fecha_registro)?->toDateTimeString(),

      // Datos bancarios origen
      'banco_pago' => $this->banco_pago,
      'cuenta_origen' => $this->cuenta_origen,
      'tipo_cuenta_origen' => $this->tipo_cuenta_origen,
      'clabe_interbancaria_origen' => $this->clabe_interbancaria_origen,

      // Datos bancarios destino
      'banco_destino' => $this->banco_destino,
      'cuenta_destino' => $this->cuenta_destino,
      'tipo_cuenta_destino' => $this->tipo_cuenta_destino,
      'clabe_interbancaria_destino' => $this->clabe_interbancaria_destino,
      'titular_cuenta_destino' => $this->titular_cuenta_destino,

      // Montos
      'monto_aplicado' => (float) $this->montoTotalAplicado(),
      'monto_disponible' => (float) $this->montoDisponible(),
      'esta_completamente_aplicado' => $this->estaCompletamenteAplicado(),

      // Metadatos
      'observaciones' => $this->observaciones,
      'usuario_registro_id' => $this->usuario_registro_id,
      'usuario_registro_nombre' => $this->usuario_registro_nombre,


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

      // Timestamps
      'created_at' => optional($this->created_at)?->toDateTimeString(),
      'updated_at' => optional($this->updated_at)?->toDateTimeString(),
    ];
  }
}
