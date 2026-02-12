<?php

namespace App\Http\Resources\Proveedor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorPagoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $proveedorId = $this->proveedor_id ?? null;
        $comprobanteUrl = null;

        $fechaPago = $this->fecha_pago ?: ($this->pivot->fecha_aplicacion ?? $this->fecha_registro);
        $montoTotal = $this->monto_total ?? ($this->pivot->monto_aplicado ?? 0);

        if ($proveedorId && $this->comprobante_pago) {
            // Usa la ruta de proveedor para descargar comprobante de pago parcial
            $comprobanteUrl = route('proveedores.pagos-spp.descargar-comprobante', [
                'proveedor' => $proveedorId,
                'pago' => $this->id,
            ]);
        }

        return [
            'id' => $this->id,
            'folio_pago_spp_consecutivo' => $this->folio_pago_spp_consecutivo,

            // Empresa Construcc
            'empresa_construcc_id' => $this->empresa_construcc_id,
            'empresa_construcc_nombre' => $this->whenLoaded('empresaConstrucc', fn () => $this->empresaConstrucc?->nombre),
            'cuenta_construcc_id' => $this->cuenta_bancaria_empresa_construcc_id,

            // Usuario registro
            'usuario_id' => $this->usuario_registro_id,
            'usuario_nombre' => $this->usuario_registro_nombre,

            // Proveedor
            'proveedor_id' => $proveedorId,
            'proveedor_nombre_comercial' => $this->whenLoaded('proveedor', fn () => $this->proveedor?->nombre_comercial),
            'proveedor_razon_social' => $this->whenLoaded('proveedor', fn () => $this->proveedor?->razon_social),
            'proveedor_rfc' => $this->whenLoaded('proveedor', fn () => $this->proveedor?->rfc),

            // Comprobante / pago
            'datos_comprobante' => [
                'comprobante_url' => $comprobanteUrl,
                'monto_total' => (float) $montoTotal,
                'fecha_registro' => optional($this->fecha_registro)?->toDateTimeString(),
                'fecha_pago' => optional($fechaPago)->toDateTimeString(),
                'referencia_pago' => $this->referencia_pago,
                'banco_destino' => $this->banco_destino,
                'titular_cuenta_destino' => $this->titular_cuenta_destino,
                'clave_rastreo' => $this->clave_rastreo,
            ],

            // Solicitudes de pago asociadas
            'solicitudes_pago' => $this->whenLoaded('solicitudesPago', function () {
                return $this->solicitudesPago->map(function ($sp) {
                    $saldoPendiente = (float) ($sp->calcularSaldoRestante() ?? 0);
                    $montoPagado = (float) ($sp->monto_total - $saldoPendiente);

                    return [
                        'id' => $sp->id,
                        'folio_sp_consecutivo' => $sp->folio_sp_consecutivo ?? null,
                        'numero_folio_solicitud' => $sp->numero_folio_solicitud ?? null,
                        'monto_total_sp' => (float) $sp->monto_total,
                        'monto_pagado' => $montoPagado,
                        'saldo_pendiente' => $saldoPendiente,
                        'tiene_factura' => $sp->tiene_factura,
                        'datos_factura' => [
                            'uso' => $sp->uso,
                            'mp' => $sp->mp,
                            'fp' => $sp->fp,
                            'datos_facturacion_id' => $sp->datos_facturacion_id,
                        ],
                        'monto_aplicado' => (float) ($sp->pivot->monto_aplicado ?? 0),
                        'estado_pago' => $sp->pivot->estado_pago ?? null,
                        'notas' => $sp->pivot->notas ?? null,
                        'fecha_aplicacion' => optional($sp->pivot->fecha_aplicacion ?? null)?->toDateTimeString(),
                    ];
                });
            }),

            // Fechas
            'fecha_pago' => optional($fechaPago)->toDateTimeString(),
            'fecha_registro' => optional($this->fecha_registro)?->toDateTimeString(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}
