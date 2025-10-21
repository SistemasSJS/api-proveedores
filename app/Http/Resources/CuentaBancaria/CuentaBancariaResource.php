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

// <?php

// namespace App\Http\Resources\CuentaBancaria;

// use Illuminate\Http\Resources\Json\JsonResource;

// class CuentaBancariaResource extends JsonResource
// {
//     public function toArray($request)
//     {
//         // Determinar qué campo usar según el tipo de cuenta o campo_dependiente
//         $cuentaValor = null;
//         $clabeValor = null;
//         $tarjetaValor = null;

//         switch ($this->tipo_cuenta) {
//             case 'cuenta':
//                 $cuentaValor = $this->campo_dependiente;
//                 break;
//             case 'clabe_interbancaria':
//                 $clabeValor = $this->campo_dependiente;
//                 break;
//             case 'tarjeta':
//                 $tarjetaValor = $this->campo_dependiente;
//                 break;
//         }

//         return [
//             'id' => $this->id,
//             'alias' => $this->alias,
//             'banco_clave' => $this->banco_clave,
//             'banco_nombre' => $this->banco_nombre,
//             'tipo_cuenta' => $this->tipo_cuenta,
//             'titular_cuenta' => $this->titular_cuenta,
//             'referencia' => $this->referencia,
//             'proveedor_id' => $this->proveedor_id,
//             'sucursal' => $this->sucursal,
//             'swift' => $this->swift,
//             'preferida' => $this->preferida,

//             // Nuevos campos
//             'cuenta' => $cuentaValor,
//             'clabe_interbancaria' => $clabeValor,
//             'tarjeta' => $tarjetaValor,
//         ];
//     }
// }
