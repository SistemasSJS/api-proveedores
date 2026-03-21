<?php

namespace App\Http\Resources\Presupuesto;

use App\Models\Presupuesto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación completa del presupuesto básico.
 */
class PresupuestoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'numero_presupuesto' => $this->numero_presupuesto,
            'fecha_emision' => $this->fecha_emision?->format('Y-m-d'),
            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),
            'concepto_general' => $this->concepto_general,
            'subtotal' => (float) $this->subtotal,
            'con_iva' => (bool) $this->con_iva,
            'iva_porcentaje' => (float) $this->iva_porcentaje,
            'iva_total' => (float) $this->iva_total,
            'total' => (float) $this->total,
            'term_cond_dias_vigencia' => $this->term_cond_dias_vigencia,
            'term_cond_moneda' => $this->term_cond_moneda ?? 'MXN',
            'term_cond_iva' => (float) ($this->term_cond_iva ?? 16),
            'term_cond_anticipo_porcentaje' => $this->term_cond_anticipo_porcentaje,
            'term_cond_tiempo_entrega_dias' => $this->term_cond_tiempo_entrega_dias,
            'obs_garantia_dias' => (int) ($this->obs_garantia_dias ?? 0),
            'obs_traslados' => (bool) ($this->obs_traslados ?? false),
            'obs_viaticos' => (bool) ($this->obs_viaticos ?? false),
            'estado' => $this->estado ?? Presupuesto::ESTADO_BORRADOR,
            'item_visto' => (bool) ($this->item_visto ?? false),
            'token_publico' => $this->token_publico,
            'proveedor' => [
                'id' => $this->proveedor?->id ?? $this->proveedor_id,
                'nombre' => $this->proveedor?->nombre_comercial
                    ?? $this->proveedor?->razon_social
                    ?? null,
            ],
            'empresa_receptora' => [
                'id' => $this->empresaReceptora?->id ?? $this->empresa_receptora_id,
                'nombre' => $this->empresaReceptora?->nombre ?? $this->empresa_receptora_nombre,
                'puesto' => $this->empresaReceptora?->puesto ?? $this->empresa_receptora_puesto,
                'empresa' => $this->empresaReceptora?->empresa ?? $this->empresa_receptora_empresa,
                'alias_empresa' => $this->empresa_receptora_alias ?? $this->empresaReceptora?->alias_empresa,
                'telefono' => $this->empresa_receptora_telefono,
                'correo' => $this->empresa_receptora_correo,
                'origen' => $this->empresa_receptora_id ? 'cartera' : 'captura',
            ],
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                ];
            }),
            'empresa_receptora_nombre' => $this->empresa_receptora_nombre,
            'empresa_receptora_puesto' => $this->empresa_receptora_puesto,
            'empresa_receptora_empresa' => $this->empresa_receptora_empresa,
            'empresa_receptora_alias' => $this->empresa_receptora_alias,
            'empresa_receptora_telefono' => $this->empresa_receptora_telefono,
            'empresa_receptora_correo' => $this->empresa_receptora_correo,
            'conceptos' => PresupuestoConceptoResource::collection($this->whenLoaded('conceptos')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
