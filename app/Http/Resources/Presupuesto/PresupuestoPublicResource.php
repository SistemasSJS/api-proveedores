<?php

namespace App\Http\Resources\Presupuesto;

use App\Models\Presupuesto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Representación pública del presupuesto (sin datos sensibles).
 */
class PresupuestoPublicResource extends JsonResource
{
    /**
     * Convierte un string a mayúsculas (UTF-8). Null o vacío se devuelven tal cual.
     */
    private static function upper(?string $value): ?string
    {
        return $value !== null && $value !== '' ? Str::upper($value) : $value;
    }

    public function toArray(Request $request): array
    {
        $proveedor = $this->proveedor;
        $condiciones = is_array($this->configuracion_condiciones) ? $this->configuracion_condiciones : [];
        $condiciones = array_merge($condiciones, [
            'vigencia_dias' => $this->term_cond_dias_vigencia,
            'impuestos_activo' => $this->term_cond_impuestos_en_pdf !== false,
            'anticipo_porcentaje' => $this->term_cond_anticipo_porcentaje,
            'tiempo_entrega_dias' => $this->term_cond_tiempo_entrega_dias,
            'garantia_dias' => $this->obs_garantia_dias,
            'gastos_traslado' => $this->obs_traslados === null
                ? null
                : ($this->obs_traslados ? 'incluidos' : 'no_incluidos'),
            'viaticos' => $this->obs_viaticos === null
                ? null
                : ($this->obs_viaticos ? 'incluidos' : 'no_incluidos'),
        ]);
        $logoUrl = null;
        if ($proveedor && $proveedor->logo) {
            $logoUrl = preg_match('/^https?:\/\//', $proveedor->logo)
                ? $proveedor->logo
                : Storage::disk('public')->url($proveedor->logo);
        }

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
            'condiciones' => $condiciones,
            'observaciones' => null,
            'term_cond_dias_vigencia' => $this->term_cond_dias_vigencia,
            'term_cond_moneda' => $this->term_cond_moneda ?? 'MXN',
            'term_cond_iva' => (float) ($this->term_cond_iva ?? 16),
            'term_cond_anticipo_porcentaje' => $this->term_cond_anticipo_porcentaje,
            'term_cond_tiempo_entrega_dias' => $this->term_cond_tiempo_entrega_dias,
            'obs_garantia_dias' => (int) ($this->obs_garantia_dias ?? 0),
            'obs_traslados' => $this->obs_traslados === null ? null : (bool) $this->obs_traslados,
            'obs_viaticos' => $this->obs_viaticos === null ? null : (bool) $this->obs_viaticos,
            'motivo_rechazo' => $this->motivo_rechazo,
            'estado' => $this->estado ?? Presupuesto::ESTADO_BORRADOR,
            'proveedor' => [
                'id' => $proveedor?->id ?? $this->proveedor_id,
                'nombre' => self::upper($proveedor?->nombre_comercial ?? $proveedor?->razon_social ?? null),
                'logo' => $logoUrl,
                'rfc' => $proveedor?->rfc ?? null,
                'direccion_empresa' => self::upper($proveedor?->direccion_empresa ?? null),
                'ciudad' => self::upper($proveedor?->ciudad ?? null),
                'estado' => self::upper($proveedor?->estado ?? null),
                'telefono' => $proveedor?->telefono ?? null,
                'email' => $proveedor?->email ?? null,
            ],
            'empresa_receptora' => [
                'nombre' => $this->empresa_receptora_nombre,
                'puesto' => $this->empresa_receptora_puesto,
                'empresa' => $this->empresa_receptora_empresa,
                'alias_empresa' => $this->empresa_receptora_alias,
                'telefono' => $this->empresa_receptora_telefono,
                'correo' => $this->empresa_receptora_correo,
            ],
            'conceptos' => PresupuestoConceptoResource::collection($this->whenLoaded('conceptos')),
        ];
    }
}
