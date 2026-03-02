<?php

namespace App\Http\Resources\Presupuesto;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación completa del presupuesto básico.
 */
class PresupuestoResource extends JsonResource
{
    /**
     * Transforma el recurso en arreglo.
     *
     * Campos incluidos:
     * - Identificadores y datos fiscales del presupuesto.
     * - Totales monetarios.
     * - Relación emisor/receptor/usuario.
     * - Conceptos (cuando están cargados).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_presupuesto' => $this->numero_presupuesto,
            'fecha_emision' => $this->fecha_emision?->format('Y-m-d'),
            'concepto_general' => $this->concepto_general,
            'subtotal' => (float) $this->subtotal,
            'con_iva' => (bool) $this->con_iva,
            'iva_porcentaje' => (float) $this->iva_porcentaje,
            'iva_total' => (float) $this->iva_total,
            'total' => (float) $this->total,
            'condiciones' => $this->condiciones,
            'observaciones' => $this->observaciones,
            'proveedor' => $this->whenLoaded('proveedor', function () {
                return [
                    'id' => $this->proveedor?->id,
                    'nombre' => $this->proveedor?->nombre_comercial ?? $this->proveedor?->razon_social,
                ];
            }),
            'empresa_receptora' => $this->whenLoaded('empresaReceptora', function () {
                return [
                    'id' => $this->empresaReceptora?->id,
                    'nombre' => $this->empresaReceptora?->nombre_comercial ?? $this->empresaReceptora?->razon_social,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                ];
            }),
            'conceptos' => PresupuestoConceptoResource::collection($this->whenLoaded('conceptos')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

