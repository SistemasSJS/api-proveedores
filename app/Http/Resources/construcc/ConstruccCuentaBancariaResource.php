<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Resources\Json\JsonResource;

class ConstruccCuentaBancariaResource extends JsonResource
{
  public function toArray($request)
  {
    // Determinar qué campo usar según el tipo de cuenta
    $cuentaValor = null;
    $clabeValor = null;
    $tarjetaValor = null;

    switch ($this->tipo_cuenta) {
      case 'cuenta':
        $cuentaValor = $this->campo_dependiente;
        break;
      case 'clabe':
        $clabeValor = $this->campo_dependiente;
        break;
      case 'tarjeta':
        $tarjetaValor = $this->campo_dependiente;
        break;
    }

    return [
      // ----------------------------
      // Sección: Identificación
      // ----------------------------
      'identificacion' => [
        'id' => $this->id,
        'alias' => $this->alias,
        'tipo_cuenta' => $this->tipo_cuenta,
        'preferida' => $this->preferida,
      ],

      // ----------------------------
      // Sección: Banco
      // ----------------------------
      'banco' => [
        'banco_clave' => $this->banco_clave,
        'banco_nombre' => $this->banco_nombre,
        'sucursal' => $this->sucursal,
        'swift' => $this->swift,
      ],

      // ----------------------------
      // Sección: Propietario
      // ----------------------------
      'propietario' => [
        'titular_cuenta' => $this->titular_cuenta,
        'referencia' => $this->referencia,
        'proveedor_id' => $this->proveedor_id,
      ],

      // ----------------------------
      // Sección: Datos de pago
      // ----------------------------
      'datos_pago' => [
        'cuenta' => $cuentaValor,
        'clabe_interbancaria' => $clabeValor,
        'tarjeta' => $tarjetaValor,
      ],
    ];
  }
}
