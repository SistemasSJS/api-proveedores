<?php

namespace App\Rules;

use App\Models\Producto;
use Illuminate\Contracts\Validation\Rule;

class StockSuficiente implements Rule
{
    private $cantidad;

    private $sucursalId;

    public function __construct($cantidad, $sucursalId = null)
    {
        $this->cantidad = $cantidad;
        $this->sucursalId = $sucursalId;
    }

    public function passes($attribute, $value)
    {
        $producto = Producto::find($value);

        if (! $producto) {
            return false;
        }

        if ($this->sucursalId) {
            $stockDisponible = $producto->getStockEnSucursal($this->sucursalId);
        } else {
            $stockDisponible = $producto->stock;
        }

        return $stockDisponible >= $this->cantidad;
    }

    public function message()
    {
        return 'No hay stock suficiente para la cantidad solicitada.';
    }
}
