<?php

namespace App\Http\Resources\SolicitudPago;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudPagoCuentaBancariaResource extends JsonResource
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
            'cuenta_bancaria_id' => $this->cuenta_bancaria_id,

            // Datos de la cuenta bancaria en la solicitud
            'alias' => $this->alias,
            'banco_clave' => $this->banco_clave,
            'banco_nombre' => $this->banco_nombre,
            'tipo_cuenta' => $this->tipo_cuenta,
            'campo_dependiente' => $this->campo_dependiente,
            'titular_cuenta' => $this->titular_cuenta,
            'referencia' => $this->referencia,
            'estatus' => $this->estatus,
            'sucursal' => $this->sucursal,
            'swift' => $this->swift,
            'preferida' => (bool) $this->preferida,

            // Relación con la cuenta bancaria original (opcional)
            'cuenta_bancaria_original' => $this->whenLoaded('cuentaBancaria', function () {
                return [
                    'id' => $this->cuentaBancaria->id,
                    'alias' => $this->cuentaBancaria->alias,
                    'banco_nombre' => $this->cuentaBancaria->banco_nombre,
                    'tipo_cuenta' => $this->cuentaBancaria->tipo_cuenta,
                    'referencia' => $this->cuentaBancaria->referencia,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
