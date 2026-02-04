<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ConstruccPagoProveedorResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  Request  $request
   * @return array
   */
  public function toArray($request): array
  {
    $count = (int) ($this->spp_autorizadas_count ?? 0);

    return [
      'id' => $this->id,
      'nombre_comercial' =>  $this->nombre_comercial,

      // Contadores
      'spp_autorizadas_count' => $count,

      // Cuentas bancarias
      'cuentas_bancarias' => $this->whenLoaded('cuentasBancarias', function () {
        return $this->cuentasBancarias->map(function ($cuenta) {
          return [
            'id' => $cuenta->id,
            'alias' => $cuenta->alias,
            'banco_clave' => $cuenta->banco_clave,
            'banco_nombre' => $cuenta->banco_nombre,
            'tipo_cuenta' => $cuenta->tipo_cuenta,
            'titular_cuenta' => $cuenta->titular_cuenta,
            'referencia' => $cuenta->referencia,
            'estatus' => $cuenta->estatus?->value ?? null,
            'sucursal' => $cuenta->sucursal,
            'swift' => $cuenta->swift,
            'preferida' => (bool) $cuenta->preferida,
          ];
        });
      }),
    ];
  }
}
