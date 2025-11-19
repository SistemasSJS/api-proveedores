<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConstruccSolicitudPagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'usuario' => $this->usuario_id,
            'numero_folio_solicitud' => $this->numero_folio_solicitud,
            'descripcion_concepto' => $this->descripcion_concepto,
            'observaciones' => $this->observaciones,
            'monto_total' => $this->monto_total,
            'proveedor' => new ConstruccProveedorResource($this->whenLoaded('proveedor')),
            'cuentas_bancarias' => ConstruccCuentaBancariaResource::collection(
                $this->whenLoaded('cuentasBancarias')
            ),
            'cotizacion' => new ConstruccCotizacionResource($this->whenLoaded('cotizacion')),
            'ruta_archivo_factura_xml' => $this->ruta_archivo_factura_xml,
            'ruta_archivo_factura_pdf' => $this->ruta_archivo_factura_pdf,
            'ruta_archivo_cotizacion' => $this->ruta_archivo_cotizacion,
            'ruta_archivo_comprobante_pago' => $this->ruta_archivo_comprobante_pago,

            // NUEVO CAMPO
            'verificada' => $this->verificada ? 1 : 0,
            // Usuario Construcc que generó la SP
            'usuario_id' => $this->usuario_id,
            'usuario_nombre' => $this->usuario_nombre,
            // Campos de tipo y origen
            'tipo' => $this->tipo,
            'tipo_id' => $this->tipo_id,
            'obra_id' => $this->obra_id,
            'observaciones' => $this->observaciones,

            // Archivos con URLs correctas
            'url_comprobante_pago' => $this->ruta_archivo_comprobante_pago
                ? route('construcc.solicitudes-pago.descargar-comprobante', $this->id)
                : null,

            'url_factura_pdf' => $this->ruta_archivo_factura_pdf
                ? route('construcc.solicitudes-pago.descargar-factura-pdf', $this->id)
                : null,

            'url_factura_xml' => $this->ruta_archivo_factura_xml
                ? route('construcc.solicitudes-pago.descargar-factura-xml', $this->id)
                : null,

            'url_cotizacion' => $this->ruta_archivo_cotizacion
                ? route('construcc.solicitudes-pago.descargar-cotizacion', $this->id)
                : null,

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

            'estado_solicitud' => $this->estado_solicitud,
            'motivo_rechazo' => $this->motivo_rechazo,
            'fecha_rechazo' => $this->fecha_rechazo?->format('Y-m-d H:i:s'),
            'fecha_pago' => $this->fecha_pago?->format('Y-m-d H:i:s'),

            // Campos de abono y pagos parciales
            'monto_total' => (float) $this->monto_total,
            'monto_abonado' => (float) $this->monto_abonado,
            'saldo_pendiente' => (float) $this->saldo_pendiente,
            'pago_completo' => (bool) $this->pago_completo,
            'notas_abono' => $this->notas_abono,
            'porcentaje_pagado' => $this->monto_total > 0 ? round(($this->monto_abonado / $this->monto_total) * 100, 2) : 0,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
