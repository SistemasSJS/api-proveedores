<?php

namespace App\Http\Resources\Presupuesto;

use App\Models\Presupuesto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Representación pública del presupuesto (sin datos sensibles).
 */
class PresupuestoPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $proveedor = $this->proveedor;
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
            'condiciones' => $this->condiciones,
            'observaciones' => $this->observaciones,
            'estado' => $this->estado ?? Presupuesto::ESTADO_BORRADOR,
            'proveedor' => [
                'id' => $proveedor?->id ?? $this->proveedor_id,
                'nombre' => $proveedor?->nombre_comercial ?? $proveedor?->razon_social ?? null,
                'logo' => $logoUrl,
                'rfc' => $proveedor?->rfc ?? null,
                'direccion_empresa' => $proveedor?->direccion_empresa ?? null,
                'ciudad' => $proveedor?->ciudad ?? null,
                'estado' => $proveedor?->estado ?? null,
                'telefono' => $proveedor?->telefono ?? null,
                'email' => $proveedor?->email ?? null,
            ],
            'empresa_receptora' => [
                'nombre' => $this->empresa_receptora_nombre,
                'puesto' => $this->empresa_receptora_puesto,
                'empresa' => $this->empresa_receptora_empresa,
                'telefono' => $this->empresa_receptora_telefono,
                'correo' => $this->empresa_receptora_correo,
            ],
            'conceptos' => PresupuestoConceptoResource::collection($this->whenLoaded('conceptos')),
        ];
    }
}
