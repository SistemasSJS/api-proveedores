<?php

namespace App\Http\Resources\Presupuesto;

use App\Models\PresupuestoConcepto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación de un concepto individual del presupuesto.
 */
class PresupuestoConceptoResource extends JsonResource
{
    /**
     * Transforma el recurso en arreglo.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => (int) $this->numero,
            'tipo' => $this->tipo ?? PresupuestoConcepto::TIPO_CONCEPTO,
            'descripcion' => $this->descripcion,
            'cantidad' => (float) $this->cantidad,
            'unidad' => $this->unidad,
            'precio_unitario' => (float) $this->precio_unitario,
            'precio_total' => (float) $this->precio_total,
        ];
    }
}

