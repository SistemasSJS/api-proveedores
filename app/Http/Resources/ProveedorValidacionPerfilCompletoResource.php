<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorValidacionPerfilCompletoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'puede_generar_sp' => $this->resource['puede_generar_sp'],
            'detalle' => [
                'perfil_empresa_completo' => $this->resource['detalle']['perfil_empresa_completo'],
                'tiene_cuenta_bancaria' => $this->resource['detalle']['tiene_cuenta_bancaria'],
                'tiene_constancia_fiscal' => $this->resource['detalle']['tiene_constancia_fiscal'],
                'tiene_logo' => $this->resource['detalle']['tiene_logo'],
                'tiene_informacion_general_y_datos_fiscales' => $this->resource['detalle']['tiene_informacion_general_y_datos_fiscales'],
                'datos_faltantes' => $this->resource['detalle']['datos_faltantes'] ?? []
            ]
        ];
    }
}