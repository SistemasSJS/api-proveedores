<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConstruccSolicitudPagoListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'numero_folio_solicitud' => $this->numero_folio_solicitud,
            'descripcion_concepto'   => $this->descripcion_concepto,
            'monto_total'            => $this->monto_total,
            'proveedor'              => new ConstruccProveedorResource($this->whenLoaded('proveedor')),
            'cotizacion'             => new ConstruccCotizacionResource($this->whenLoaded('cotizacion')),
            // Archivos con URLs correctas
            'url_cotizacion_pdf' => $this->ruta_archivo_factura_pdf
                ? route('construcc.solicitudes-pago.descargar-factura-pdf', $this->id)
                : null,

            'url_comprobante_pago' => $this->ruta_archivo_comprobante_pago
                ? route('construcc.solicitudes-pago.descargar-comprobante', $this->id)
                : null,

            'url_factura_pdf' => $this->ruta_archivo_factura_pdf
                ? route('construcc.solicitudes-pago.descargar-factura-pdf', $this->id)
                : null,

            'url_factura_xml' => $this->ruta_archivo_factura_xml
                ? route('construcc.solicitudes-pago.descargar-factura-xml', $this->id)
                : null,

            // Campos de aprobación
            'dg'       => $this->dg?->value,
            'dg_fecha' => $this->dg_fecha?->format('Y-m-d H:i:s'),

            'dt'       => $this->dt?->value,
            'dt_fecha' => $this->dt_fecha?->format('Y-m-d H:i:s'),

            'pc'       => $this->pc?->value,
            'pc_fecha' => $this->pc_fecha?->format('Y-m-d H:i:s'),

            'si'       => $this->si?->value,
            'si_fecha' => $this->si_fecha?->format('Y-m-d H:i:s'),

            'ro'       => $this->ro?->value,
            'ro_fecha' => $this->ro_fecha?->format('Y-m-d H:i:s'),

            'estado_solicitud' => $this->estado_solicitud,
            'motivo_rechazo'   => $this->motivo_rechazo,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
