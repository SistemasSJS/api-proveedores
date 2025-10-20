<?php

namespace App\Rules;

use App\Models\Producto;
use Illuminate\Contracts\Validation\Rule;

class ProductoBelongsToProveedor implements Rule
{
    private $proveedorId;

    private $message;

    public function __construct($proveedorId)
    {
        $this->proveedorId = $proveedorId;
    }

    public function passes($attribute, $value)
    {
        $producto = Producto::find($value);

        if (! $producto) {
            $this->message = 'El producto seleccionado no existe.';

            return false;
        }

        if ($producto->proveedor_id != $this->proveedorId) {
            $this->message = 'El producto no pertenece al proveedor especificado.';

            return false;
        }

        if (! $producto->activo) {
            $this->message = 'El producto seleccionado no está activo.';

            return false;
        }

        return true;
    }

    public function message()
    {
        return $this->message;
    }
}
