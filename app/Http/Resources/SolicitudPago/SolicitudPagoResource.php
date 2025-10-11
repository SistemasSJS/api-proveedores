<?php

namespace App\Http\Resources\SolicitudPago;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudPagoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id,
            'numero_folio_solicitud'      => $this->numero_folio_solicitud,
            'descripcion_concepto'        => $this->descripcion_concepto,
            'estado_solicitud'            => $this->estado_solicitud,

            // Archivos
            'ruta_archivo_factura_xml'    => $this->ruta_archivo_factura_xml,
            'ruta_archivo_factura_pdf'    => $this->ruta_archivo_factura_pdf,
            'ruta_archivo_cotizacion'     => $this->ruta_archivo_cotizacion,
            'ruta_archivo_comprobante_pago' => $this->ruta_archivo_comprobante_pago,

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

            // Fechas principales
            'fecha_registro_pendiente'    => $this->fecha_registro_pendiente?->format('Y-m-d H:i:s'),
            'fecha_inicio_procesamiento'  => $this->fecha_inicio_procesamiento?->format('Y-m-d H:i:s'),
            'fecha_aprobado'              => $this->fecha_aprobado?->format('Y-m-d H:i:s'),
            'fecha_rechazado'             => $this->fecha_rechazado?->format('Y-m-d H:i:s'),
            'fecha_con_comprobante'       => $this->fecha_con_comprobante?->format('Y-m-d H:i:s'),
            'fecha_confirmacion_pago'     => $this->fecha_confirmacion_pago?->format('Y-m-d H:i:s'),

            // Nuevos campos booleanos + fechas
            'dg'                          => (bool) $this->dg,
            'dg_fecha'                    => $this->dg_fecha?->format('Y-m-d H:i:s'),

            'dt'                          => (bool) $this->dt,
            'dt_fecha'                    => $this->dt_fecha?->format('Y-m-d H:i:s'),

            'pc'                          => (bool) $this->pc,
            'pc_fecha'                    => $this->pc_fecha?->format('Y-m-d H:i:s'),

            'si'                          => (bool) $this->si,
            'si_fecha'                    => $this->si_fecha?->format('Y-m-d H:i:s'),

            'da'                          => (bool) $this->da,
            'da_fecha'                    => $this->da_fecha?->format('Y-m-d H:i:s'),

            'ro'                          => (bool) $this->ro,
            'ro_fecha'                    => $this->ro_fecha?->format('Y-m-d H:i:s'),

            'estado_solicitud' => $this->estado_solicitud,
            'motivo_rechazo'   => $this->motivo_rechazo,

            // Campos de abono y pagos parciales
            'monto_total'                 => (float) $this->monto_total,
            'monto_abonado'               => (float) $this->monto_abonado,
            'saldo_pendiente'             => (float) $this->saldo_pendiente,
            'pago_completo'               => (bool) $this->pago_completo,
            'notas_abono'                 => $this->notas_abono,
            'porcentaje_pagado'           => $this->monto_total > 0 ? round(($this->monto_abonado / $this->monto_total) * 100, 2) : 0,

            // Metadatos
            'created_at'                  => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'                  => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
