<?php

namespace App\Http\Resources\SolicitudPago;

use App\Http\Resources\ProveedorResource;
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
            'ruta_archivo_comprobante_pago' => $this->ruta_archivo_comprobante_pago,

            // Fechas
            'fecha_registro_pendiente'    => $this->fecha_registro_pendiente?->format('Y-m-d H:i:s'),
            'fecha_inicio_procesamiento'  => $this->fecha_inicio_procesamiento?->format('Y-m-d H:i:s'),
            'fecha_aprobado'              => $this->fecha_aprobado?->format('Y-m-d H:i:s'),
            'fecha_rechazado'             => $this->fecha_rechazado?->format('Y-m-d H:i:s'),
            'fecha_con_comprobante'       => $this->fecha_con_comprobante?->format('Y-m-d H:i:s'),
            'fecha_confirmacion_pago'     => $this->fecha_confirmacion_pago?->format('Y-m-d H:i:s'),

            'created_at'                  => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'                  => $this->updated_at?->format('Y-m-d H:i:s'),

            // Campos adicionales
            // 'residente'                   => $this->residente,
            // 'cotizacion_id'               => $this->cotizacion_id,
            // 'empresa_construcc_id'        => $this->empresa_construcc_id,

            // Motivos de rechazo (si aplica)
            // 'motivo_rechazo'              => $this->motivo_rechazo,

            // Relaciones
            // 'proveedor' => new ProveedorResource($this->whenLoaded('proveedor')),
            // 'empresa_construcc' => $this->when($this->relationLoaded('empresaConstrucc'), function () {
            //     return [
            //         'id'                => $this->empresaConstrucc->id,
            //         'nombre'            => $this->empresaConstrucc->nombre,
            //         'rfc'               => $this->empresaConstrucc->rfc,
            //         'razon_social'      => $this->empresaConstrucc->razon_social,
            //         'representante_legal' => $this->empresaConstrucc->representante_legal,
            //     ];
            // }),
        ];
    }
}
