<?php

namespace App\Http\Resources\Presupuesto;

use App\Models\Presupuesto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Representación completa del presupuesto básico.
 */
class PresupuestoResource extends JsonResource
{
    /**
     * Convierte un string a mayúsculas (UTF-8). Null o vacío se devuelven tal cual.
     */
    private static function upper(?string $value): ?string
    {
        return $value !== null && $value !== '' ? Str::upper($value) : $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $proveedorNombre = $this->proveedor?->nombre_comercial
            ?? $this->proveedor?->razon_social
            ?? null;

        $configCond = $this->configuracion_condiciones;
        $proveedorReceptorId = (int) ($this->proveedor_receptor_id ?? 0);
        if ($proveedorReceptorId <= 0 && is_array($configCond) && isset($configCond['proveedor_receptor_id'])) {
            $proveedorReceptorId = (int) $configCond['proveedor_receptor_id'];
        }
        $receptorEsProveedorCatalogo = $proveedorReceptorId > 0
            || (is_array($configCond) && ! empty($configCond['receptor_es_proveedor_catalogo']));
        $empresaReceptoraIdRespuesta = $this->empresaReceptora?->id
            ?? ($proveedorReceptorId > 0 ? $proveedorReceptorId : null)
            ?? $this->empresa_receptora_id;
        $origenReceptor = $receptorEsProveedorCatalogo
            ? 'proveedor'
            : ($this->empresa_receptora_id ? 'cartera' : 'captura');

        $doc = $this->resource->empresaReceptoraParaDocumento();

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
            'term_cond_impuestos_en_pdf' => (bool) ($this->term_cond_impuestos_en_pdf ?? true),
            'term_cond_iva' => (float) ($this->term_cond_iva ?? 16),
            'term_cond_anticipo_porcentaje' => $this->term_cond_anticipo_porcentaje,
            'term_cond_tiempo_entrega_dias' => $this->term_cond_tiempo_entrega_dias,
            'term_cond_inicio_trabajo' => $this->term_cond_inicio_trabajo,
            'term_cond_inicio_trabajo_porcentaje' => $this->term_cond_inicio_trabajo_porcentaje,
            'obs_garantia_dias' => (int) ($this->obs_garantia_dias ?? 0),
            'obs_traslados' => $this->obs_traslados === null ? null : (bool) $this->obs_traslados,
            'obs_viaticos' => $this->obs_viaticos === null ? null : (bool) $this->obs_viaticos,
            'configuracion_condiciones' => $this->configuracion_condiciones,
            'estado' => $this->estado ?? Presupuesto::ESTADO_BORRADOR,
            'motivo_rechazo' => $this->motivo_rechazo,
            'item_visto' => (bool) ($this->item_visto ?? false),
            'token_publico' => $this->token_publico,
            'proveedor' => [
                'id' => $this->proveedor?->id ?? $this->proveedor_id,
                'nombre' => self::upper($proveedorNombre),
            ],
            'proveedor_receptor_id' => $this->proveedor_receptor_id !== null
                ? (int) $this->proveedor_receptor_id
                : null,
            'empresa_receptora' => [
                'id' => $empresaReceptoraIdRespuesta,
                'nombre' => $doc['nombre'],
                'puesto' => $doc['puesto'],
                'empresa' => $doc['empresa'],
                'alias_empresa' => $doc['alias_empresa'],
                'telefono' => $doc['telefono'],
                'correo' => $doc['correo'],
                'origen' => $origenReceptor,
            ],
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                ];
            }),
            'empresa_receptora_nombre' => $doc['nombre'],
            'empresa_receptora_puesto' => $doc['puesto'],
            'empresa_receptora_empresa' => $doc['empresa'],
            'empresa_receptora_alias' => $doc['alias_empresa'],
            'empresa_receptora_telefono' => $doc['telefono'],
            'empresa_receptora_correo' => $doc['correo'],
            'conceptos' => PresupuestoConceptoResource::collection($this->whenLoaded('conceptos')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
