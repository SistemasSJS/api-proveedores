<?php

namespace App\Http\Resources\CuentaBancaria;

use Illuminate\Http\Resources\Json\JsonResource;

class CuentaBancariaResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'alias' => $this->alias,
      'banco_clave' => $this->banco_clave,
      'banco_nombre' => $this->banco_nombre,
      'tipo_cuenta' => $this->tipo_cuenta,
      'campo_dependiente' => $this->campo_dependiente,
      'titular_cuenta' => $this->titular_cuenta,
      'referencia' => $this->referencia,
      'proveedor_id' => $this->proveedor_id,
      'sucursal' => $this->sucursal,
      'swift' => $this->swift,
      'preferida' => $this->preferida,
    ];
  }
}
