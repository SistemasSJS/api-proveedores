<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class  ConstruccPagoProveedorResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  Request  $request
   * @return array
   */
  public function toArray($request): array
  {
    return [
      'id' => $this->id,

      // Datos generales del pago
      'fecha_pago' => $this->fecha_pago?->toDateString(),
      'fecha_registro' => $this->fecha_registro?->toDateTimeString(),
      'referencia_pago' => $this->referencia_pago,

      // Comprobante
      'comprobante_pago' => $this->comprobante_pago
        ? asset('storage/' . $this->comprobante_pago)
        : null,

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
      'monto_total' => (float) $this->monto_total,

      // Metadatos
      'observaciones' => $this->observaciones,
      'usuario_registro' => [
        'id' => $this->usuario_registro_id,
        'nombre' => $this->usuario_registro_nombre,
      ],

      // Empresa Construcc
      // 'empresa_construcc' => $this->whenLoaded(
      //   'empresaConstrucc',
      //   fn() => new EmpresaConstruccResource($this->empresaConstrucc)
      // ),

      // SPPs asociadas (pivot)
      // 'solicitudes_pago' => SolicitudPagoPagoResource::collection(
      // $this->whenLoaded('solicitudesPago')
      // ),
    ];
  }
}
