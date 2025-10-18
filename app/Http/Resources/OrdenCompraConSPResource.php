<?php

namespace App\Http\Resources;

use App\Http\Resources\SolicitudPago\SolicitudPagoResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenCompraConSPResource extends JsonResource
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
            'numero_orden' => $this->numero_orden,
            'fecha_orden' => $this->fecha_orden?->format('Y-m-d'),
            'fecha_aprobacion' => $this->fecha_aprobacion?->format('Y-m-d H:i:s'),
            'importe_total' => (float) $this->importe_total,
            'estado' => [
                'codigo' => $this->estado->value,
                'label' => $this->estado->label(),
                'color' => $this->estado->color(),
            ],
            'observaciones' => $this->observaciones,
            
            // Información específica de SP
            'sp_info' => [
                'sp_count' => (int) $this->sp_count,
                'monto_sp_asociado' => (float) $this->monto_sp_asociado,
                'monto_disponible' => (float) $this->getMontoDisponible(),
                'porcentaje_convertido' => $this->importe_total > 0 ? 
                    round(($this->monto_sp_asociado / $this->importe_total) * 100, 2) : 0,
                'puede_generar_sp' => $this->puedeGenerarSolicitudPago(),
                'permite_pagos_parciales' => $this->estado === \App\Enums\EstadoOrdenCompra::APROBADA,
            ],
            
            // Alertas
            'alerta_info' => [
                'dias_sin_sp' => $this->dias_sin_sp ?? $this->getDiasSinSolicitudPago(),
                'nivel_alerta' => $this->nivel_alerta ?? $this->getNivelAlerta(),
                'mensaje_alerta' => $this->mensaje_alerta ?? null,
                'prioridad' => $this->prioridad ?? null,
            ],
            
            // Relaciones básicas
            'proveedor' => $this->whenLoaded('proveedor', function () {
                return [
                    'id' => $this->proveedor->id,
                    'nombre_comercial' => $this->proveedor->nombre_comercial,
                    'rfc' => $this->proveedor->rfc,
                ];
            }),
            
            'empresa_construcc' => $this->whenLoaded('empresaConstrucc', function () {
                return [
                    'id' => $this->empresaConstrucc->id,
                    'nombre' => $this->empresaConstrucc->nombre,
                    'rfc' => $this->empresaConstrucc->rfc,
                ];
            }),
            
            // Solicitudes de pago detalladas
            'solicitudes_pago' => $this->whenLoaded('solicitudesPago', function () {
                return $this->solicitudesPago->map(function ($sp) {
                    return [
                        'id' => $sp->id,
                        'numero_folio_solicitud' => $sp->numero_folio_solicitud,
                        'descripcion_concepto' => $sp->descripcion_concepto,
                        'monto_total' => (float) $sp->monto_total,
                        'estado_solicitud' => $sp->estado_solicitud,
                        'fecha_creacion' => $sp->created_at?->format('Y-m-d H:i:s'),
                        'residente' => $sp->residente,
                        
                        // Información de vinculación con OC
                        'vinculacion' => [
                            'monto_asociado' => (float) $sp->pivot->monto_asociado,
                            'fecha_vinculacion' => $sp->pivot->fecha_vinculacion?->format('Y-m-d H:i:s'),
                            'notas' => $sp->pivot->notas,
                        ],
                        
                        // Estado del pago
                        'pago_info' => [
                            'monto_abonado' => (float) $sp->monto_abonado,
                            'saldo_pendiente' => (float) $sp->saldo_pendiente,
                            'pago_completo' => (bool) $sp->pago_completo,
                        ],
                    ];
                });
            }),
            
            // Resumen de detalles (sin detalles completos para evitar payload grande)
            'detalles_resumen' => $this->whenLoaded('detalles', function () {
                return [
                    'total_items' => $this->detalles->count(),
                    'productos' => $this->detalles->pluck('producto')->take(3)->toArray(),
                    'mas_productos' => $this->detalles->count() > 3,
                ];
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'conversion_info' => [
                    'monto_minimo_sp' => config('ordenes-compra.conversion.monto_minimo_sp', 0.01),
                    'permite_pagos_parciales' => config('ordenes-compra.conversion.permite_pagos_parciales', true),
                ],
                'estados_sp' => [
                    'pendiente' => 'Pendiente',
                    'en_proceso' => 'En Proceso', 
                    'aprobada' => 'Aprobada',
                    'pagada' => 'Pagada',
                    'rechazada' => 'Rechazada',
                ],
            ],
        ];
    }
}