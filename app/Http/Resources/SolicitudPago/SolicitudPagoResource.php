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
            'id'                        => $this->id,
            'numero_folio_solicitud'    => $this->numero_folio_solicitud,
            'descripcion_concepto'      => $this->descripcion_concepto,
            'ruta_archivo_factura_xml'  => $this->ruta_archivo_factura_xml,
            'ruta_archivo_factura_pdf'  => $this->ruta_archivo_factura_pdf,
            'estado_solicitud'          => $this->estado_solicitud,
            'ruta_archivo_comprobante_pago' => $this->ruta_archivo_comprobante_pago,


            'fecha_registro_pendiente'  => $this->fecha_registro_pendiente?->format('Y-m-d H:i:s'),
            'fecha_inicio_procesamiento' => $this->fecha_inicio_procesamiento?->format('Y-m-d H:i:s'),
            'fecha_confirmacion_pago'   => $this->fecha_confirmacion_pago?->format('Y-m-d H:i:s'),
            'created_at'                => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'                => $this->updated_at?->format('Y-m-d H:i:s'),

            // Relaciones
            'proveedor' => new ProveedorResource($this->whenLoaded('proveedor')),
            // // Nuevos campos
            // 'empresa_construcc_id'      => $this->empresa_construcc_id,
            // 'residente'                 => $this->residente,
            // 'cotizacion_id'             => $this->cotizacion_id,
            // 'empresa_construcc' => $this->when($this->relationLoaded('empresaConstrucc'), function () {
            //     return [
            //         'id' => $this->empresaConstrucc->id,
            //         'nombre' => $this->empresaConstrucc->nombre,
            //         'rfc' => $this->empresaConstrucc->rfc,
            //         'razon_social' => $this->empresaConstrucc->razon_social,
            //         'representante_legal' => $this->empresaConstrucc->representante_legal,
            //     ];
            // }),
        ];
    }
}
