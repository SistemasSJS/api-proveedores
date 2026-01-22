<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoSPPResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Datos del comprobante y fechas
            'comprobante_pago' => $this->comprobante_pago,
            'comprobante_pago_url' => $this->comprobante_pago 
                ? asset('storage/' . $this->comprobante_pago) 
                : null,
            'fecha_pago' => $this->fecha_pago?->format('Y-m-d H:i:s'),
            'fecha_registro' => $this->fecha_registro?->format('Y-m-d H:i:s'),
            
            // Referencia de pago
            'referencia_pago' => $this->referencia_pago,
            
            // Datos bancarios del pago (origen)
            'banco_pago' => $this->banco_pago,
            'cuenta_origen' => $this->cuenta_origen,
            'tipo_cuenta_origen' => $this->tipo_cuenta_origen,
            'clabe_interbancaria_origen' => $this->clabe_interbancaria_origen,
            
            // Datos bancarios del proveedor (destino)
            'banco_destino' => $this->banco_destino,
            'cuenta_destino' => $this->cuenta_destino,
            'tipo_cuenta_destino' => $this->tipo_cuenta_destino,
            'clabe_interbancaria_destino' => $this->clabe_interbancaria_destino,
            'titular_cuenta_destino' => $this->titular_cuenta_destino,
            
            // Montos
            'monto_total' => (float) $this->monto_total,
            'monto_aplicado' => $this->whenLoaded('solicitudesPago', function () {
                return $this->solicitudesPago->sum('pivot.monto_aplicado');
            }),
            'monto_disponible' => $this->when(
                $this->relationLoaded('solicitudesPago'),
                function () {
                    $montoAplicado = $this->solicitudesPago->sum('pivot.monto_aplicado');
                    return (float) ($this->monto_total - $montoAplicado);
                }
            ),
            
            // Metadatos
            'observaciones' => $this->observaciones,
            'usuario_registro_id' => $this->usuario_registro_id,
            'usuario_registro_nombre' => $this->usuario_registro_nombre,
            'empresa_construcc_id' => $this->empresa_construcc_id,
            
            // Relaciones
            'empresa_construcc' => $this->whenLoaded('empresaConstrucc'),
            'solicitudes_pago' => $this->whenLoaded('solicitudesPago', function () {
                return $this->solicitudesPago->map(function ($solicitud) {
                    return [
                        'id' => $solicitud->id,
                        'numero_folio_solicitud' => $solicitud->numero_folio_solicitud,
                        'descripcion_concepto' => $solicitud->descripcion_concepto,
                        'monto_total' => (float) $solicitud->monto_total,
                        'estado_solicitud' => $solicitud->estado_solicitud,
                        'proveedor' => $solicitud->whenLoaded('proveedor'),
                        
                        // Datos del pivot
                        'pivot' => [
                            'monto_aplicado' => (float) $solicitud->pivot->monto_aplicado,
                            'estado_pago' => $solicitud->pivot->estado_pago,
                            'notas' => $solicitud->pivot->notas,
                            'fecha_aplicacion' => $solicitud->pivot->fecha_aplicacion?->format('Y-m-d H:i:s'),
                        ],
                    ];
                });
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
